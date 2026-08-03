import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const WAYFINDING_VIRTUAL_SOURCES = {
    'virtual:wayfinding-core': [
    'public/js/wayfinding-routing.js',
    'public/js/wayfinding/01-map-core.js',
    'public/js/wayfinding/02-map-data-ui.js',
    'public/js/wayfinding/03-outdoor-routing.js',
    'public/js/wayfinding/04-indoor-routing.js',
    'public/js/wayfinding/05-map-rendering.js',
    'public/js/wayfinding/06-search-voice.js',
    'public/js/wayfinding/07-campus-events-data.js',
    'public/js/wayfinding/08-navigation-accessibility.js',
    'public/js/wayfinding/09-building-indoor-ui.js',
    'public/js/wayfinding/10-responsive-performance.js',
    'public/js/wayfinding/14-pwa-offline.js',
    ],
    'virtual:wayfinding-assistant': [
        'public/js/wayfinding/08-assistant-ui.js',
    ],
    'virtual:wayfinding-gps': [
        'public/js/wayfinding/12-gps-tracking.js',
    ],
    'virtual:wayfinding-gps-diagnostics': [
        'public/js/wayfinding/13-gps-diagnostics.js',
    ],
};

const WAYFINDING_VIRTUAL_IDS = new Map(
    Object.keys(WAYFINDING_VIRTUAL_SOURCES)
        .map(id => [id, `\0${id}`])
);

function orderedWayfindingBundle() {
    return {
        name: 'ordered-wayfinding-bundle',
        resolveId(id) {
            return WAYFINDING_VIRTUAL_IDS.get(id) || null;
        },
        load(id) {
            const virtualId = Array.from(WAYFINDING_VIRTUAL_IDS.entries())
                .find(([, resolvedId]) => resolvedId === id)?.[0];
            if (!virtualId) return null;

            const source = WAYFINDING_VIRTUAL_SOURCES[virtualId]
                .map(file => {
                    const content = readFileSync(resolve(process.cwd(), file), 'utf8');
                    return `\n/* source: ${file} */\n${content}`;
                })
                .join('\n');

            if (virtualId !== 'virtual:wayfinding-core') {
                return source;
            }

            /*
             * These handlers are called from Blade/dynamic HTML attributes.
             * Explicit exports keep the optimized ES module compatible with
             * those existing interactions and prevent tree-shaking.
             */
            const browserExports = `
                const exposeWayfindingBinding = (name, getter, setter = null) => {
                    const descriptor = Object.getOwnPropertyDescriptor(window, name);
                    if (descriptor && !descriptor.configurable) return;

                    Object.defineProperty(window, name, {
                        configurable: true,
                        enumerable: false,
                        get: getter,
                        set: setter || undefined,
                    });
                };

                exposeWayfindingBinding('map', () => map);
                exposeWayfindingBinding('pathGeojson', () => pathGeojson);
                exposeWayfindingBinding('entryPoints', () => entryPoints);
                exposeWayfindingBinding('routeLayer', () => routeLayer, value => { routeLayer = value; });
                exposeWayfindingBinding('startMarker', () => startMarker, value => { startMarker = value; });
                exposeWayfindingBinding('startNodeKey', () => startNodeKey, value => { startNodeKey = value; });
                exposeWayfindingBinding('startSourceType', () => startSourceType, value => { startSourceType = value; });
                exposeWayfindingBinding('placingStartMode', () => placingStartMode, value => { placingStartMode = value; });
                exposeWayfindingBinding('selectedStartMode', () => selectedStartMode, value => { selectedStartMode = value; });
                exposeWayfindingBinding('selectedBuildingEntranceId', () => selectedBuildingEntranceId, value => { selectedBuildingEntranceId = value; });
                exposeWayfindingBinding('selectedDestinationBuildingId', () => selectedDestinationBuildingId, value => { selectedDestinationBuildingId = value; });
                exposeWayfindingBinding('selectedDestinationLanduseId', () => selectedDestinationLanduseId, value => { selectedDestinationLanduseId = value; });
                exposeWayfindingBinding('selectedIndoorRoomFeature', () => selectedIndoorRoomFeature, value => { selectedIndoorRoomFeature = value; });
                exposeWayfindingBinding('isVoiceListening', () => isVoiceListening, value => { isVoiceListening = value; });
                exposeWayfindingBinding('speechRecognition', () => speechRecognition, value => { speechRecognition = value; });
                exposeWayfindingBinding('voiceSupported', () => voiceSupported, value => { voiceSupported = value; });
                exposeWayfindingBinding('landuseRecords', () => landuseRecords);
                exposeWayfindingBinding('destinationLanduseSelect', () => destinationLanduseSelect);
                exposeWayfindingBinding('destinationTypeSelect', () => destinationTypeSelect);

                exposeWayfindingBinding('clearCurrentLocationMarker', () => clearCurrentLocationMarker);
                exposeWayfindingBinding('clearOutsideGuideLine', () => clearOutsideGuideLine);
                exposeWayfindingBinding('clearRouteLayer', () => clearRouteLayer, value => { clearRouteLayer = value; });
                exposeWayfindingBinding('clearStartMarker', () => clearStartMarker);
                exposeWayfindingBinding('dijkstra', () => dijkstra);
                exposeWayfindingBinding('dijkstraAsync', () => dijkstraAsync);
                exposeWayfindingBinding('drawOutdoorRoute', () => drawOutdoorRoute, value => { drawOutdoorRoute = value; });
                exposeWayfindingBinding('drawOutsideGuideLine', () => drawOutsideGuideLine);
                exposeWayfindingBinding('hidePickPathHelper', () => hidePickPathHelper);
                exposeWayfindingBinding('isInsideCampus', () => isInsideCampus);
                exposeWayfindingBinding('nearestNodeKey', () => nearestNodeKey);
                exposeWayfindingBinding('parseCoordKey', () => parseCoordKey);
                exposeWayfindingBinding('resetRouteSelection', () => resetRouteSelection, value => { resetRouteSelection = value; });
                exposeWayfindingBinding('selectDefaultMode', () => selectDefaultMode, value => { selectDefaultMode = value; });
                exposeWayfindingBinding('selectGpsMode', () => selectGpsMode, value => { selectGpsMode = value; });
                exposeWayfindingBinding('selectPickPathMode', () => selectPickPathMode, value => { selectPickPathMode = value; });
                exposeWayfindingBinding('setActiveStartModeButton', () => setActiveStartModeButton);
                exposeWayfindingBinding('setRouteResultLabel', () => setRouteResultLabel);
                exposeWayfindingBinding('showPickPathHelper', () => showPickPathHelper);
                exposeWayfindingBinding('updatePickPathHelperText', () => updatePickPathHelperText);
                exposeWayfindingBinding('updateRouteLabels', () => updateRouteLabels);

                exposeWayfindingBinding('closeBrowseOptionsModal', () => closeBrowseOptionsModal, value => { closeBrowseOptionsModal = value; });
                exposeWayfindingBinding('closeFloatingActionCard', () => closeFloatingActionCard, value => { closeFloatingActionCard = value; });
                exposeWayfindingBinding('closeIndoorPanelFn', () => closeIndoorPanelFn, value => { closeIndoorPanelFn = value; });
                exposeWayfindingBinding('closeTextSearchModal', () => closeTextSearchModal, value => { closeTextSearchModal = value; });
                exposeWayfindingBinding('enablePathStartPlacement', () => enablePathStartPlacement, value => { enablePathStartPlacement = value; });
                exposeWayfindingBinding('ensureDefaultStartBeforeRoute', () => ensureDefaultStartBeforeRoute);
                exposeWayfindingBinding('findRouteByDestination', () => findRouteByDestination, value => { findRouteByDestination = value; });
                exposeWayfindingBinding('initVoiceRecognition', () => initVoiceRecognition, value => { initVoiceRecognition = value; });
                exposeWayfindingBinding('isDesignLanduse', () => isDesignLanduse);
                exposeWayfindingBinding('loadAllData', () => loadAllData);
                exposeWayfindingBinding('openBrowseOptionsModal', () => openBrowseOptionsModal, value => { openBrowseOptionsModal = value; });
                exposeWayfindingBinding('openIndoorPanelForBuilding', () => openIndoorPanelForBuilding, value => { openIndoorPanelForBuilding = value; });
                exposeWayfindingBinding('openTextSearchModal', () => openTextSearchModal, value => { openTextSearchModal = value; });
                exposeWayfindingBinding('searchTextDestination', () => searchTextDestination, value => { searchTextDestination = value; });
                exposeWayfindingBinding('setHeardText', () => setHeardText);
                exposeWayfindingBinding('setIndoorLoading', () => setIndoorLoading);
                exposeWayfindingBinding('setVoiceStatus', () => setVoiceStatus);
                exposeWayfindingBinding('startVoiceCommand', () => startVoiceCommand, value => { startVoiceCommand = value; });
                exposeWayfindingBinding('startVoiceSearchFlow', () => startVoiceSearchFlow, value => { startVoiceSearchFlow = value; });
                exposeWayfindingBinding('stopVoiceCommand', () => stopVoiceCommand, value => { stopVoiceCommand = value; });
                exposeWayfindingBinding('toggleDestinationMenu', () => toggleDestinationMenu, value => { toggleDestinationMenu = value; });
                exposeWayfindingBinding('toggleFloatingActionCard', () => toggleFloatingActionCard, value => { toggleFloatingActionCard = value; });
                exposeWayfindingBinding('toggleVoiceCommand', () => toggleVoiceCommand, value => { toggleVoiceCommand = value; });
                exposeWayfindingBinding('updateDestinationUi', () => updateDestinationUi);
                exposeWayfindingBinding('updateVoiceButtonUi', () => updateVoiceButtonUi);
                exposeWayfindingBinding('useCurrentLocationAsStart', () => useCurrentLocationAsStart, value => { useCurrentLocationAsStart = value; });
                exposeWayfindingBinding('useDefaultEntryPointAsStart', () => useDefaultEntryPointAsStart, value => { useDefaultEntryPointAsStart = value; });

                window.cancelPickPathMode = cancelPickPathMode;
                window.setBrowseDestinationType = setBrowseDestinationType;
                window.selectBrowseRoom = selectBrowseRoom;
                window.toggleUserProfileMenu = toggleUserProfileMenu;
                window.WayfindingCrBridge = Object.freeze({
                    getRooms: () => [...(allIndoorRooms.features || [])],
                    prepareRooms: rooms => Promise.all(
                        Array.from(new Set((rooms || [])
                            .map(room => Number(room?.properties?.building_id || 0))
                            .filter(Boolean)))
                            .map(buildingId => ensureIndoorBuildingData(buildingId))
                    ),
                    getStartState: () => ({
                        key: startNodeKey,
                        source: startSourceType,
                        mode: selectedStartMode,
                        placing: placingStartMode,
                    }),
                    chooseStartMode(mode) {
                        if (mode === 'gps') return window.selectGpsMode();
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
                    async routeToRoom(room) {
                        const roomId = Number(room?.properties?.id || 0);
                        if (!roomId) return false;
                        setBrowseDestinationType('room');
                        selectBrowseRoom(roomId);
                        await computeCompleteRouteToRoom(room);
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
