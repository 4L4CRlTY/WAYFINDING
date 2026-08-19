<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wayfinding Mobile QA</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #071522;
            color: #e8f7ff;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(25, 203, 239, .14), transparent 28rem),
                #071522;
        }

        .qa-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(260px, 330px) minmax(0, 1fr);
            gap: 24px;
            padding: 24px;
        }

        .qa-controls {
            align-self: start;
            position: sticky;
            top: 24px;
            padding: 22px;
            border: 1px solid #174a63;
            border-radius: 18px;
            background: #0a2132;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .3);
        }

        .qa-kicker {
            margin: 0 0 8px;
            color: #42dbf5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
        }

        h1 { margin: 0; font-size: 23px; }

        .qa-note {
            margin: 9px 0 20px;
            color: #9ebac9;
            font-size: 13px;
            line-height: 1.5;
        }

        .qa-field { display: grid; gap: 7px; margin-top: 15px; }
        .qa-field > span { color: #b9d1dc; font-size: 12px; font-weight: 700; }

        select,
        button,
        a {
            min-height: 44px;
            border-radius: 11px;
            font: inherit;
        }

        select {
            width: 100%;
            padding: 0 12px;
            border: 1px solid #27617b;
            background: #071b2a;
            color: #eefaff;
        }

        .qa-profile-switch {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .qa-profile-switch button,
        .qa-reload {
            border: 1px solid #27617b;
            background: #0c2a3d;
            color: #bcd2dc;
            cursor: pointer;
            font-weight: 800;
        }

        .qa-profile-switch button[aria-pressed="true"] {
            border-color: #31d6f4;
            background: #0b5573;
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(49, 214, 244, .3);
        }

        .qa-reload { width: 100%; margin-top: 18px; }
        .qa-reload:hover { border-color: #31d6f4; color: #fff; }

        .qa-open {
            display: grid;
            place-items: center;
            margin-top: 9px;
            border: 1px solid transparent;
            color: #83def0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .qa-device-stage {
            min-width: 0;
            display: grid;
            justify-items: center;
            align-content: start;
            overflow: auto;
            padding: 10px 20px 40px;
        }

        .qa-status {
            margin: 0 0 10px;
            color: #8eb1c0;
            font-size: 12px;
            font-weight: 700;
        }

        .qa-phone {
            padding: 10px;
            border: 1px solid #426778;
            border-radius: 30px;
            background: #02080d;
            box-shadow: 0 26px 70px rgba(0, 0, 0, .48);
        }

        iframe {
            display: block;
            border: 0;
            border-radius: 21px;
            background: #fff;
        }

        @media (max-width: 840px) {
            .qa-shell { grid-template-columns: 1fr; padding: 14px; }
            .qa-controls { position: static; }
            .qa-device-stage { justify-items: start; padding-inline: 0; }
        }
    </style>
</head>
<body>
    <main class="qa-shell">
        <section class="qa-controls" aria-labelledby="qa-title">
            <p class="qa-kicker">LOCAL DEVELOPMENT ONLY</p>
            <h1 id="qa-title">Mobile QA Emulator</h1>
            <p class="qa-note">
                Test the real dashboard at a phone viewport. This route and its profile override are not registered in production.
            </p>

            <div class="qa-field">
                <span>Performance profile</span>
                <div class="qa-profile-switch" role="group" aria-label="Performance profile">
                    <button type="button" data-profile="low" aria-pressed="true">Mobile Low</button>
                    <button type="button" data-profile="balanced" aria-pressed="false">Mobile Advanced</button>
                </div>
            </div>

            <label class="qa-field">
                <span>Screen size</span>
                <select id="qa-size">
                    <option value="360x800">360 × 800 — compact Android</option>
                    <option value="390x844" selected>390 × 844 — standard phone</option>
                    <option value="412x915">412 × 915 — large Android</option>
                </select>
            </label>

            <label class="qa-field">
                <span>Dashboard</span>
                <select id="qa-target">
                    <option value="{{ route('user.dashboard') }}">User dashboard — all features</option>
                    <option value="{{ route('guest.dashboard') }}">Guest dashboard</option>
                </select>
            </label>

            <button type="button" class="qa-reload" id="qa-reload">Reload clean session</button>
            <a class="qa-open" id="qa-open" href="#" target="_blank" rel="noopener">Open simulated page in a new tab</a>
        </section>

        <section class="qa-device-stage" aria-label="Simulated mobile phone">
            <p class="qa-status" id="qa-status" aria-live="polite"></p>
            <div class="qa-phone">
                <iframe id="qa-frame" title="Wayfinding mobile emulator"></iframe>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const frame = document.getElementById('qa-frame');
            const sizeSelect = document.getElementById('qa-size');
            const targetSelect = document.getElementById('qa-target');
            const status = document.getElementById('qa-status');
            const openLink = document.getElementById('qa-open');
            const profileButtons = [...document.querySelectorAll('[data-profile]')];
            let profile = 'low';

            const buildUrl = (fresh = false) => {
                const url = new URL(targetSelect.value, window.location.origin);
                url.searchParams.set('mobile_emulator', profile);
                if (fresh) url.searchParams.set('qa', Date.now().toString());
                return url.toString();
            };

            const render = (fresh = false) => {
                const [width, height] = sizeSelect.value.split('x').map(Number);
                frame.width = String(width);
                frame.height = String(height);
                frame.src = buildUrl(fresh);
                openLink.href = buildUrl(false);
                status.textContent = `${profile === 'low' ? 'Mobile Low' : 'Mobile Advanced'} · ${width} × ${height}`;
            };

            profileButtons.forEach(button => {
                button.addEventListener('click', () => {
                    profile = button.dataset.profile;
                    profileButtons.forEach(item => item.setAttribute(
                        'aria-pressed',
                        item === button ? 'true' : 'false'
                    ));
                    render(true);
                });
            });

            sizeSelect.addEventListener('change', () => render(true));
            targetSelect.addEventListener('change', () => render(true));
            document.getElementById('qa-reload').addEventListener('click', () => render(true));
            render(true);
        })();
    </script>
</body>
</html>
