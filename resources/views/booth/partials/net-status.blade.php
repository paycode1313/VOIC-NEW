{{-- Operator chip: online, or offline with the local backups (AI and voice) taking over. Polls every 20 s. --}}
<span x-data="{ s: null, async load() { try { this.s = await (await fetch(@js(route('attract.status')), { headers: { Accept: 'application/json' } })).json() } catch (e) { this.s = { internet: false, ai_service: false } } } }"
      x-init="load(); setInterval(() => load(), 20000)" x-show="s" x-cloak
      class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
      :class="s && s.internet ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'"
      :title="s && !s.internet ? 'Internet putus: AI lokal dan suara offline dipakai otomatis' : ''">
    <span class="h-2 w-2 rounded-full" :class="s && s.internet ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
    <span x-text="!s ? '' : (s.internet ? 'Online' : 'Mode offline: AI & suara cadangan')"></span>
    <span x-show="s && !s.ai_service" class="text-rose-700">· AI service mati</span>
</span>
