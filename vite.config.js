import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const WAYFINDING_VIRTUAL_ID = 'virtual:wayfinding-user';
const WAYFINDING_RESOLVED_ID = `\0${WAYFINDING_VIRTUAL_ID}`;
const WAYFINDING_SOURCE_FILES = [
    'public/js/wayfinding-routing.js',
    'public/js/wayfinding/01-map-core.js',
    'public/js/wayfinding/02-map-data-ui.js',
    'public/js/wayfinding/03-outdoor-routing.js',
    'public/js/wayfinding/04-indoor-routing.js',
    'public/js/wayfinding/05-map-rendering.js',
    'public/js/wayfinding/06-search-voice.js',
    'public/js/wayfinding/07-campus-events-data.js',
    'public/js/wayfinding/08-navigation-accessibility.js',
    'public/js/wayfinding/08-assistant-ui.js',
    'public/js/wayfinding/09-building-indoor-ui.js',
    'public/js/wayfinding/10-responsive-performance.js',
    'public/js/wayfinding/11-map-performance.js',
    'public/js/wayfinding/12-gps-tracking.js',
    'public/js/wayfinding/13-gps-diagnostics.js',
    'public/js/wayfinding/14-pwa-offline.js',
];

function orderedWayfindingBundle() {
    return {
        name: 'ordered-wayfinding-bundle',
        resolveId(id) {
            return id === WAYFINDING_VIRTUAL_ID ? WAYFINDING_RESOLVED_ID : null;
        },
        load(id) {
            if (id !== WAYFINDING_RESOLVED_ID) return null;

            const source = WAYFINDING_SOURCE_FILES
                .map(file => {
                    const content = readFileSync(resolve(process.cwd(), file), 'utf8');
                    return `\n/* source: ${file} */\n${content}`;
                })
                .join('\n');

            /*
             * These handlers are called from Blade/dynamic HTML attributes.
             * Explicit exports keep the optimized ES module compatible with
             * those existing interactions and prevent tree-shaking.
             */
            const browserExports = `
                window.cancelPickPathMode = cancelPickPathMode;
                window.setBrowseDestinationType = setBrowseDestinationType;
                window.selectBrowseRoom = selectBrowseRoom;
                window.toggleUserProfileMenu = toggleUserProfileMenu;
                window.WayfindingCrBridge = Object.freeze({
                    getRooms: () => [...(allIndoorRooms.features || [])],
                    getStartState: () => ({
                        key: startNodeKey,
                        source: startSourceType,
                        mode: selectedStartMode,
                        placing: placingStartMode,
                    }),
                    chooseStartMode(mode) {
                        if (mode === 'gps') return selectGpsMode();
                        if (mode === 'path') return selectPickPathMode();
                        return selectDefaultMode();
                    },
                    estimateRoom(room) {
                        const result = findBestEntranceLinkForRoom(room);
                        return result ? {
                            totalCost: Number(result.totalCost || 0),
                            outdoorCost: Number(result.outdoorCost || 0),
                            indoorCost: Number(result.indoorCost || 0),
                        } : null;
                    },
                    routeToRoom(room) {
                        const roomId = Number(room?.properties?.id || 0);
                        if (!roomId) return false;
                        setBrowseDestinationType('room');
                        selectBrowseRoom(roomId);
                        computeCompleteRouteToRoom(room);
                        return true;
                    },
                });

                if (window.WAYFINDING_GPS_SIMULATOR_ENABLED === true) {
                    window.WayfindingSimulatorBridge = {
                        get map() { return map; },
                        get outdoorNodeCoords() { return outdoorNodeCoords; },
                        nearestNodeKey(lat, lng) { return nearestNodeKey(lat, lng); },
                        isInsideCampus(lat, lng) { return isInsideCampus(lat, lng); },
                    };
                }
            `;

            return `
                import Leaflet from 'leaflet';
                window.L = Leaflet;
                ${source}
                ${browserExports}
            `;
        },
    };
}

export default defineConfig({
    plugins: [
        orderedWayfindingBundle(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/wayfinding.css',
                'resources/js/wayfinding-entry.js',
            ],
            refresh: true,
        }),
    ],
});
