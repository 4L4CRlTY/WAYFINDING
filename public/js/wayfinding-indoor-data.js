/* Deferred indoor graph transport. Loaded only when indoor routing is requested. */
'use strict';

(function exposeIndoorDataLoader(global) {
    async function load(buildingId, dependencies = {}) {
        const normalizedBuildingId = Number(buildingId || 0);
        const loadSnapshot = dependencies.loadSnapshot;
        const fetchJson = dependencies.fetchJson;

        if (!normalizedBuildingId || typeof loadSnapshot !== 'function' || typeof fetchJson !== 'function') {
            throw new Error('Indoor data loader received invalid dependencies.');
        }

        const snapshot = await loadSnapshot();
        const template = snapshot?.indoor_data_url_template || '/data/indoor/{building}.json';
        const staticUrl = template.replace('{building}', String(normalizedBuildingId));

        try {
            const response = await fetch(staticUrl, {
                cache: 'no-cache',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error('Indoor snapshot unavailable.');

            const document = await response.json();
            if (
                Number(document?.schema_version) !== 1
                || Number(document?.building_id) !== normalizedBuildingId
                || !document?.datasets
            ) {
                throw new Error('Indoor snapshot format is invalid.');
            }

            return document.datasets;
        } catch (error) {
            const suffix = `?building_id=${encodeURIComponent(normalizedBuildingId)}`;
            const [paths, entrances, stairs] = await Promise.all([
                fetchJson(`/api/indoor-paths${suffix}`),
                fetchJson(`/api/indoor-entrances${suffix}`),
                fetchJson(`/api/indoor-stairs-links${suffix}`)
            ]);

            return {
                '/api/indoor-paths': paths,
                '/api/indoor-entrances': entrances,
                '/api/indoor-stairs-links': stairs
            };
        }
    }

    global.WayfindingIndoorDataLoader = Object.freeze({ load });
})(self);
