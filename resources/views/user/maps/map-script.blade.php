<script>
    var map = L.map('map', {
        zoomControl: true,
        minZoom: 18.3,
        maxZoom: 19
    });

    // Setup custom panes aron mu-ibabaw ang buildings sa paths
    map.createPane('pathsPane');
    map.getPane('pathsPane').style.zIndex = 400;

    map.createPane('buildingsPane');
    map.getPane('buildingsPane').style.zIndex = 450;

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO',
        maxZoom: 19
    }).addTo(map);

    function updateShadows() {
        let currentZoom = map.getZoom();
        let step = 1 * Math.pow(2, currentZoom - 19);

        if (step < 0.1) step = 0;
        if (step > 3) step = 3;

        document.documentElement.style.setProperty('--step', `${step}px`);
    }

    map.on('zoomend', updateShadows);
</script>
