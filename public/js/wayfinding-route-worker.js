/* Browser-side route worker. It never reads GPS and never changes route rules. */
'use strict';

importScripts('/js/wayfinding-routing.js');

let outdoorGraph = null;
let snapshotVersion = null;

self.addEventListener('message', event => {
    const message = event.data || {};
    const requestId = Number(message.requestId || 0);

    try {
        if (message.type === 'init') {
            outdoorGraph = message.graph || null;
            snapshotVersion = message.snapshotVersion || null;
            self.postMessage({
                type: 'ready',
                requestId,
                snapshotVersion
            });
            return;
        }

        if (message.type !== 'route') return;

        if (!outdoorGraph) {
            throw Object.assign(new Error('Route graph is not initialized.'), {
                code: 'NOT_INITIALIZED'
            });
        }

        const result = self.WayfindingRouting.outdoorShortestPath(
            outdoorGraph,
            String(message.startKey || ''),
            String(message.endKey || '')
        );

        self.postMessage({
            type: 'result',
            requestId,
            snapshotVersion,
            result
        });
    } catch (error) {
        self.postMessage({
            type: 'error',
            requestId,
            snapshotVersion,
            error: {
                code: String(error?.code || 'ROUTE_WORKER_ERROR'),
                message: String(error?.message || 'Route worker failed.')
            }
        });
    }
});
