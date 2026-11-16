"""
DFD renderer (Yourdon/DeMarco notation) -> standalone SVG + HTML.

Shapes
  ext   : external entity, rectangle
  proc  : process, circle with its number above a divider and the name below
  store : data store, two parallel lines with the store ID in a left compartment
          (dup=True draws the duplicate mark: an extra vertical line, the usual convention)
Edges
  (src, dst, label, opts) where opts may hold:
    off  : shift the whole arrow sideways (px), for two flows between the same pair
    bend : curve the arrow sideways (px at the midpoint)
    t    : where the label sits along the arrow (0..1, default .5)
    lo   : label offset sideways from the arrow (px)
usage: python dfd.py <spec.json> <out-basename> <fonts-dir>
"""

import base64
import json
import math
import os
import sys
from html import escape

INK = "#0f172a"
SUB = "#475569"
LINE = "#334155"
PROC_FILL = "#eef2ff"
PROC_STROKE = "#4f46e5"
EXT_FILL = "#fff7ed"
EXT_STROKE = "#c2410c"
STORE_FILL = "#ecfdf5"
STORE_STROKE = "#047857"


def lines_of(text):
    return text.split("\n")


def boundary(node, toward):
    """Point where a line from the node's centre toward `toward` leaves the node's outline."""
    x, y = node["x"], node["y"]
    dx, dy = toward[0] - x, toward[1] - y
    dist = math.hypot(dx, dy) or 1.0
    ux, uy = dx / dist, dy / dist
    if node["type"] == "proc":
        r = node.get("r", 78)
        return x + ux * r, y + uy * r
    w, h = node["w"] / 2, node["h"] / 2
    scale = min(w / abs(ux) if ux else 1e9, h / abs(uy) if uy else 1e9)
    return x + ux * scale, y + uy * scale


def text_block(x, y, text, size, weight=500, color=INK, anchor="middle", line_h=1.25):
    rows = lines_of(text)
    top = y - (len(rows) - 1) * size * line_h / 2
    spans = "".join(
        f'<tspan x="{x:.1f}" y="{top + i * size * line_h:.1f}">{escape(row)}</tspan>' for i, row in enumerate(rows)
    )
    return (f'<text font-size="{size}" font-weight="{weight}" fill="{color}" text-anchor="{anchor}" '
            f'dominant-baseline="middle">{spans}</text>')


def draw_node(n):
    t = n["type"]
    x, y = n["x"], n["y"]
    if t == "ext":
        w, h = n["w"], n["h"]
        return (f'<rect x="{x - w / 2:.1f}" y="{y - h / 2:.1f}" width="{w}" height="{h}" rx="4" fill="{EXT_FILL}" '
                f'stroke="{EXT_STROKE}" stroke-width="2.2"/>' + text_block(x, y, n["label"], 16, 700))
    if t == "proc":
        r = n.get("r", 78)
        out = (f'<circle cx="{x}" cy="{y}" r="{r}" fill="{PROC_FILL}" stroke="{PROC_STROKE}" stroke-width="2.4"/>'
               f'<line x1="{x - r * 0.83:.1f}" y1="{y - r * 0.42:.1f}" x2="{x + r * 0.83:.1f}" y2="{y - r * 0.42:.1f}" '
               f'stroke="{PROC_STROKE}" stroke-width="1.6"/>')
        out += text_block(x, y - r * 0.64, n["id"], 15, 800, PROC_STROKE)
        out += text_block(x, y + r * 0.18, n["label"], 15, 700)
        return out
    # data store
    w, h = n["w"], n["h"]
    left, top = x - w / 2, y - h / 2
    sid = n["sid"]
    out = (f'<rect x="{left:.1f}" y="{top:.1f}" width="{w}" height="{h}" fill="{STORE_FILL}" stroke="none"/>'
           f'<line x1="{left:.1f}" y1="{top:.1f}" x2="{left + w:.1f}" y2="{top:.1f}" stroke="{STORE_STROKE}" stroke-width="2.2"/>'
           f'<line x1="{left:.1f}" y1="{top + h:.1f}" x2="{left + w:.1f}" y2="{top + h:.1f}" stroke="{STORE_STROKE}" stroke-width="2.2"/>'
           f'<line x1="{left:.1f}" y1="{top:.1f}" x2="{left:.1f}" y2="{top + h:.1f}" stroke="{STORE_STROKE}" stroke-width="2.2"/>'
           f'<line x1="{left + 46:.1f}" y1="{top:.1f}" x2="{left + 46:.1f}" y2="{top + h:.1f}" stroke="{STORE_STROKE}" stroke-width="1.6"/>')
    if n.get("dup"):
        out += f'<line x1="{left + 7:.1f}" y1="{top:.1f}" x2="{left + 7:.1f}" y2="{top + h:.1f}" stroke="{STORE_STROKE}" stroke-width="1.6"/>'
    out += text_block(left + 26 + (3 if n.get("dup") else 0), y, sid, 14, 800, STORE_STROKE)
    out += text_block(left + 46 + (w - 46) / 2, y, n["label"], 15, 700)
    return out


def draw_edge(nodes, e):
    src, dst, label = nodes[e[0]], nodes[e[1]], e[2]
    o = e[3] if len(e) > 3 else {}
    off, bend, t, lo = o.get("off", 0), o.get("bend", 0), o.get("t", 0.5), o.get("lo", 0)
    sx, sy, dx_, dy_ = src["x"], src["y"], dst["x"], dst["y"]
    length = math.hypot(dx_ - sx, dy_ - sy) or 1.0
    nx, ny = -(dy_ - sy) / length, (dx_ - sx) / length  # left-hand normal
    # Aim points: shifted centres, plus the curve's control point.
    mx, my = (sx + dx_) / 2 + nx * (off + bend * 2), (sy + dy_) / 2 + ny * (off + bend * 2)
    a = boundary(src, (mx, my) if bend else (dx_ + nx * off, dy_ + ny * off))
    b = boundary(dst, (mx, my) if bend else (sx + nx * off, sy + ny * off))
    if not bend and off:
        # For straight offset arrows keep both ends parallel to the centre line.
        a = boundary({**src, "x": sx + nx * off, "y": sy + ny * off}, (dx_ + nx * off, dy_ + ny * off))
        b = boundary({**dst, "x": dx_ + nx * off, "y": dy_ + ny * off}, (sx + nx * off, sy + ny * off))
    if bend:
        cx, cy = mx, my
        path = f'M{a[0]:.1f},{a[1]:.1f} Q{cx:.1f},{cy:.1f} {b[0]:.1f},{b[1]:.1f}'
        px = (1 - t) ** 2 * a[0] + 2 * (1 - t) * t * cx + t ** 2 * b[0]
        py = (1 - t) ** 2 * a[1] + 2 * (1 - t) * t * cy + t ** 2 * b[1]
    else:
        path = f'M{a[0]:.1f},{a[1]:.1f} L{b[0]:.1f},{b[1]:.1f}'
        px, py = a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t
    px, py = px + nx * lo, py + ny * lo
    out = f'<path d="{path}" fill="none" stroke="{LINE}" stroke-width="1.7" marker-end="url(#arrow)"/>'
    if label:
        rows = lines_of(label)
        size = 13
        width = max(len(r) for r in rows) * size * 0.55 + 14
        height = len(rows) * size * 1.25 + 8
        out += (f'<rect x="{px - width / 2:.1f}" y="{py - height / 2:.1f}" width="{width:.1f}" height="{height:.1f}" '
                f'rx="6" fill="#ffffff" fill-opacity="0.94" stroke="#e2e8f0"/>')
        out += text_block(px, py, label, size, 500, SUB)
    return out


def render(spec):
    nodes = {n["id"] if n["type"] == "proc" else n["key"]: n for n in spec["nodes"]}
    for n in spec["nodes"]:
        if n["type"] != "proc":
            nodes[n["key"]] = n
    W, H = spec["width"], spec["height"]
    body = [f'<rect width="{W}" height="{H}" fill="#ffffff"/>']
    body.append(text_block(40, 42, spec["title"], 26, 800, INK, "start"))
    body.append(text_block(40, 74, spec["subtitle"], 15, 500, SUB, "start"))
    body += [draw_edge(nodes, e) for e in spec["edges"]]
    body += [draw_node(n) for n in spec["nodes"]]
    # Legend
    lx, ly = W - 430, 30
    body.append(f'<rect x="{lx}" y="{ly}" width="400" height="66" rx="10" fill="#f8fafc" stroke="#e2e8f0"/>')
    body.append(f'<rect x="{lx + 16}" y="{ly + 22}" width="34" height="22" rx="2" fill="{EXT_FILL}" stroke="{EXT_STROKE}" stroke-width="2"/>')
    body.append(text_block(lx + 58, ly + 33, "Entitas luar", 13, 600, SUB, "start"))
    body.append(f'<circle cx="{lx + 167}" cy="{ly + 33}" r="13" fill="{PROC_FILL}" stroke="{PROC_STROKE}" stroke-width="2"/>')
    body.append(text_block(lx + 186, ly + 33, "Proses", 13, 600, SUB, "start"))
    body.append(f'<line x1="{lx + 250}" y1="{ly + 22}" x2="{lx + 290}" y2="{ly + 22}" stroke="{STORE_STROKE}" stroke-width="2"/>'
                f'<line x1="{lx + 250}" y1="{ly + 44}" x2="{lx + 290}" y2="{ly + 44}" stroke="{STORE_STROKE}" stroke-width="2"/>'
                f'<line x1="{lx + 250}" y1="{ly + 22}" x2="{lx + 250}" y2="{ly + 44}" stroke="{STORE_STROKE}" stroke-width="2"/>')
    body.append(text_block(lx + 298, ly + 33, "Penyimpanan", 13, 600, SUB, "start"))
    if spec.get("note"):
        body.append(text_block(40, H - 30, spec["note"], 13, 500, SUB, "start"))
    defs = (f'<defs><marker id="arrow" viewBox="0 0 10 10" refX="9.5" refY="5" markerWidth="9" markerHeight="9" orient="auto-start-reverse">'
            f'<path d="M0,0 L10,5 L0,10 z" fill="{LINE}"/></marker></defs>')
    return (f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" '
            f'font-family="Figtree, Segoe UI, system-ui, sans-serif">{defs}{"".join(body)}</svg>')


def main():
    spec_path, out, fonts = sys.argv[1], sys.argv[2], sys.argv[3]
    with open(spec_path, encoding="utf-8") as f:
        spec = json.load(f)
    svg = render(spec)
    faces = ""
    for weight in (500, 600, 700, 800):
        path = os.path.join(fonts, f"figtree-latin-{weight}-normal.woff2")
        data = base64.b64encode(open(path, "rb").read()).decode("ascii")
        faces += f"@font-face{{font-family:Figtree;font-weight:{weight};src:url(data:font/woff2;base64,{data}) format('woff2')}}"
    styled = svg.replace(">", f"><style>{faces}</style>", 1)
    open(out + ".svg", "w", encoding="utf-8").write(styled)
    html = (f'<!doctype html><html lang="id"><head><meta charset="utf-8"><title>{escape(spec["title"])}</title>'
            f'<style>body{{margin:0;background:#fff}}</style></head><body>{styled}</body></html>')
    open(out + ".html", "w", encoding="utf-8").write(html)
    print("saved", out + ".svg", out + ".html")


if __name__ == "__main__":
    main()
