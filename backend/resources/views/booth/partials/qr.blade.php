<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
    (function () {
        var box = document.getElementById(@js($target));
        if (!box || typeof qrcode !== 'function') return;
        var qr = qrcode(0, 'M');
        qr.addData(box.dataset.url);
        qr.make();
        box.innerHTML = qr.createSvgTag(4, 2);
    })();
</script>
