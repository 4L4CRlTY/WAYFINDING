/*
 * The Vite plugin in vite.config.js provides one ordered module containing
 * the existing shared-state wayfinding scripts. Keeping the ordering in the
 * build configuration lets production use one minified request without
 * changing the routing engine's runtime behavior.
 */
import 'virtual:wayfinding-user';
