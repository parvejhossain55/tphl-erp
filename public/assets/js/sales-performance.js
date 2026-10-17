/* Sales Performance dashboard – client interactions
 * Data comes from window.SP_DATA (rendered by SalesPerformanceController).
 */
(function () {
    'use strict';
    const D = window.SP_DATA;
    if (!D) return;

    // ---------- formatting ----------
    const nf = new Intl.NumberFormat('en-IN');
    const n = (v) => nf.format(Math.round(Number(v) || 0));
    const pct = (a, b, d = 1) => (b ? ((a / b) * 100).toFixed(d) : '0') + '%';
    const tk = (v) => {
        v = Number(v) || 0;
        if (v >= 1e7) return '৳' + (v / 1e7).toFixed(2) + ' Cr';
        if (v >= 1e5) return '৳' + (v / 1e5).toFixed(2) + ' L';
        return '৳' + nf.format(Math.round(v));
    };
    const hrs = (h) => {
        if (h === null || h === undefined) return '—';
        if (h < 1) return Math.round(h * 60) + ' min';
        if (h < 48) return (+h.toFixed(1)) + ' h';
        return (h / 24).toFixed(1) + ' d';
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const initials = (name) => String(name || '?').replace(/^(md\.?|mr\.?|mrs\.?|ms\.?)\s+/i, '').split(/\s+/).slice(0, 2).map((p) => p[0] || '').join('').toUpperCase();
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    const STAGES = D.stages; // {1: 'Leads Received', ...}
    const STAGE_KEYS = Object.keys(STAGES).map(Number);

    // ---------- funnel ----------
    const funnelState = {};
    function renderFunnel(el, m, mode, selected) {
        if (!el || !m) return;
        mode = mode || 'overall';
        selected = selected || 5;
        funnelState[el.id] = { m, mode, selected };
        const base = m.stage_1 || 0;
        let rows = '';
        STAGE_KEYS.forEach((s) => {
            const v = m['stage_' + s] || 0;
            const prev = s === 1 ? v : m['stage_' + (s - 1)] || 0;
            const den = mode === 'step' ? prev : base;
            const p = den ? (v / den) * 100 : 0;
            rows += `
                <div class="sp-funnel-row ${s === selected ? 'sel' : ''}" data-stage="${s}">
                    <span class="no">${String(s).padStart(2, '0')}</span>
                    <div><div class="nm">${esc(STAGES[s])}</div><div class="sp-bar"><span style="width:${Math.max(p, v ? 0.6 : 0).toFixed(2)}%"></span></div></div>
                    <span class="ct">${n(v)}</span>
                    <span class="pc">${p.toFixed(1)}%</span>
                </div>`;
        });
        const sel = m['stage_' + selected] || 0;
        rows += `
            <div class="sp-funnel-foot">
                <div><div class="k">Selected stage</div><div class="v">${n(sel)}</div><div class="s">${esc(STAGES[selected] || '')} · ${pct(sel, base)}</div></div>
                <div><div class="k">Lead to booking</div><div class="v">${pct(m.stage_5, base, 2)}</div><div class="s">${n(m.stage_5)} / ${n(base)}</div></div>
                <div><div class="k">Visit to booking</div><div class="v">${pct(m.stage_5, m.stage_4)}</div><div class="s">${n(m.stage_5)} / ${n(m.stage_4)}</div></div>
            </div>`;
        el.innerHTML = rows;
        $$('.sp-funnel-row', el).forEach((r) =>
            r.addEventListener('click', () => renderFunnel(el, m, funnelState[el.id].mode, Number(r.dataset.stage)))
        );
    }
    $$('[data-funnel-toggle]').forEach((tg) => {
        $$('button', tg).forEach((b) =>
            b.addEventListener('click', () => {
                $$('button', tg).forEach((x) => x.classList.toggle('on', x === b));
                const el = document.getElementById(tg.dataset.funnelToggle);
                const st = funnelState[el.id];
                if (st) renderFunnel(el, st.m, b.dataset.mode, st.selected);
            })
        );
    });

    // ---------- drawer ----------
    const drawer = $('#spDrawer'), bg = $('#spDrawerBg');
    function openDrawer(title, sub, html) {
        $('#spDrawerTitle').textContent = title;
        $('#spDrawerSub').innerHTML = sub || '';
        $('#spDrawerBody').innerHTML = html;
        drawer.classList.add('open');
        bg.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
    }
    function closeDrawer() {
        drawer.classList.remove('open');
        bg.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
    }
    bg && bg.addEventListener('click', closeDrawer);
    $('#spDrawerClose') && $('#spDrawerClose').addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && closeDrawer());

    // ---------- data-quality drill-down ----------
    $$('[data-gap]').forEach((btn) =>
        btn.addEventListener('click', async () => {
            const key = btn.dataset.gap;
            openDrawer('Loading…', '', '<div class="sp-empty"><i class="fa-regular fa-spinner fa-spin"></i> Loading records…</div>');
            try {
                const res = await fetch(`${D.baseUrl}/sales-performance/gap-details?key=${encodeURIComponent(key)}`, { credentials: 'same-origin' });
                const j = await res.json();
                const rows = j.rows || [];
                let html = `<p style="font-size:12.5px;color:var(--sp-muted);margin:0 0 12px">${esc(j.description || '')}</p>`;
                if (!rows.length) html += '<div class="sp-empty">No records.</div>';
                else {
                    html += `<table class="sp-dtable"><thead><tr><th>Lead / record</th><th>Owner · Team</th><th>Detail</th><th>Date</th></tr></thead><tbody>`;
                    rows.forEach((r) => {
                        const link = r.lead_id ? `<a href="${D.baseUrl}/view-lead/${r.lead_id}" target="_blank" style="color:var(--sp-green);font-weight:600">${esc(r.lead_name)}</a>` : `<b>${esc(r.lead_name)}</b>`;
                        html += `<tr><td>${link}<div style="color:var(--sp-soft);font-size:11px">${esc(r.phone || '')}</div></td>
                                 <td>${esc(r.owner_name)}<div style="color:var(--sp-soft);font-size:11px">${esc(r.team_name)}</div></td>
                                 <td>${esc(r.info || '')}</td><td style="white-space:nowrap">${esc((r.event_date || '').substring(0, 10))}</td></tr>`;
                    });
                    html += '</tbody></table>';
                    if (j.total > rows.length) html += `<div class="sp-note">Showing latest ${rows.length} of ${n(j.total)} records.</div>`;
                }
                openDrawer(j.label || 'Records', `${n(j.total || 0)} records`, html);
            } catch (e) {
                openDrawer('Could not load', '', '<div class="sp-empty">Failed to load records. Please retry.</div>');
            }
        })
    );

    // ---------- overview ----------
    const fo = $('#spFunnelOverview');
    if (fo) renderFunnel(fo, D.overall, 'overall', 5);

    const spark = $('#spSpark');
    if (spark && D.trend && D.trend.length > 1) {
        const vals = D.trend.map((t) => t.count);
        const max = Math.max(1, ...vals);
        const w = 300, h = 54, step = w / (vals.length - 1);
        const pts = vals.map((v, i) => [i * step, h - 4 - (v / max) * (h - 10)]);
        const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
        const area = line + ` L ${w} ${h} L 0 ${h} Z`;
        spark.innerHTML = `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none">
            <defs><linearGradient id="spg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#0b7a55" stop-opacity=".22"/><stop offset="1" stop-color="#0b7a55" stop-opacity="0"/></linearGradient></defs>
            <path d="${area}" fill="url(#spg)"/><path d="${line}" fill="none" stroke="#0b7a55" stroke-width="1.8" vector-effect="non-scaling-stroke"/></svg>`;
        spark.title = D.trend.map((t) => `${t.date}: ${t.count}`).slice(-7).join('\n');
    }

    // ---------- team tab ----------
    const teamTable = $('#spTeamTable');
    if (teamTable) {
        const real = D.teams.filter((t) => t.is_real_team);
        const other = D.teams.filter((t) => !t.is_real_team);
        let sortKey = 'confirmed_sales';
        let selectedKey = D.focusTeam && D.teams.some((t) => t.key === D.focusTeam) ? D.focusTeam : (real[0] || D.teams[0] || {}).key;

        const achCell = (t) => {
            if (t.target_achievement === null) {
                return `<div class="sp-ach" title="No booking target set – showing lead-to-sale"><div class="sp-bar blue"><span style="width:${Math.min(100, t.lead_to_sale * 10)}%"></span></div><b style="color:var(--sp-soft);font-weight:500">no target</b></div>`;
            }
            const a = t.target_achievement;
            return `<div class="sp-ach"><div class="sp-bar ${a < 60 ? 'amber' : ''}"><span style="width:${Math.min(100, a)}%"></span></div><b class="${a < 60 ? 'amber' : ''}">${a.toFixed(1)}%</b></div>`;
        };
        const row = (t, i, muted) => `
            <tr class="click ${t.key === selectedKey ? 'sel' : ''} ${muted ? 'muted' : ''}" data-key="${esc(t.key)}">
                <td>${muted ? '' : i + 1}</td>
                <td class="nm">${esc(t.name)}<small>${muted ? 'Not attributable to a team' : `${t.member_count} members${t.leaders.length ? ' · Lead: ' + esc(t.leaders.join(', ')) : ''}`}</small></td>
                <td class="num">${n(t.stage_1)}</td>
                <td class="num">${t.contact_rate}%</td>
                <td class="num">${hrs(t.median_response_hours)}</td>
                <td class="num">${n(t.follow_ups)}</td>
                <td class="num">${n(t.visits_done)}</td>
                <td class="num">${n(t.bookings)}</td>
                <td class="num">${n(t.confirmed_sales)} / ${t.target_bookings ? n(t.target_bookings) : '—'}</td>
                <td>${achCell(t)}</td>
                <td class="num">${tk(t.collection)}</td>
                <td class="num" style="${t.overdue > 0 ? 'color:var(--sp-amber);font-weight:600' : ''}">${n(t.overdue)}</td>
                <td><i class="fa-regular fa-arrow-right" style="color:var(--sp-green)"></i></td>
            </tr>`;

        function drawTeams() {
            const sorted = [...real].sort((a, b) => (b[sortKey] || 0) - (a[sortKey] || 0));
            $('tbody', teamTable).innerHTML = sorted.map((t, i) => row(t, i, false)).join('') + other.map((t) => row(t, 0, true)).join('');
            const o = D.overall;
            $('tfoot', teamTable).innerHTML = `<tr><td></td><td>Total</td><td class="num">${n(o.stage_1)}</td><td class="num">${o.contact_rate}%</td><td class="num">${hrs(o.median_response_hours)}</td>
                <td class="num">${n(o.follow_ups)}</td><td class="num">${n(o.visits_done)}</td><td class="num">${n(o.bookings)}</td>
                <td class="num">${n(o.confirmed_sales)} / ${o.target_bookings ? n(o.target_bookings) : '—'}</td><td></td><td class="num">${tk(o.collection)}</td><td class="num">${n(o.overdue)}</td><td></td></tr>`;
            $$('tbody tr', teamTable).forEach((tr) => tr.addEventListener('click', () => selectTeam(tr.dataset.key)));

            const realLeads = real.reduce((s, t) => s + t.stage_1, 0);
            const otherLeads = other.reduce((s, t) => s + t.stage_1, 0);
            const realBk = real.reduce((s, t) => s + t.bookings, 0);
            const otherBk = other.reduce((s, t) => s + t.bookings, 0);
            $('#spTeamRecon').innerHTML = `<i class="fa-regular fa-scale-balanced"></i> Reconciliation – Leads: teams <b>${n(realLeads)}</b> + unattributed <b>${n(otherLeads)}</b> = total <b>${n(o.stage_1)}</b>.
                Bookings: teams <b>${n(realBk)}</b> + unattributed <b>${n(otherBk)}</b> = total <b>${n(o.bookings)}</b>.
                ${otherLeads ? 'Unattributed rows come from unassigned leads, missing owners or users without a team – fix them in Data Quality on the Overview tab.' : ''}`;
        }

        function selectTeam(key) {
            selectedKey = key;
            $$('tbody tr', teamTable).forEach((tr) => tr.classList.toggle('sel', tr.dataset.key === key));
            const t = D.teams.find((x) => x.key === key);
            if (!t) return;
            $('#spTeamFunnelTitle').textContent = `${t.name} — Sales Funnel`;
            $('#spTeamFunnelSub').textContent = `${t.member_count} members · ${n(t.stage_1)} leads received`;
            const toggle = $('[data-funnel-toggle="spFunnelTeam"] button.on');
            renderFunnel($('#spFunnelTeam'), t, toggle ? toggle.dataset.mode : 'overall', 5);

            const members = D.executives.filter((e) => e.team_key === key).sort((a, b) => b.confirmed_sales - a.confirmed_sales || b.bookings - a.bookings || b.stage_1 - a.stage_1);
            $('#spTopExecSub').textContent = `${t.name} · showing ${Math.min(6, members.length)} of ${members.length}`;
            $('#spTopExec tbody').innerHTML = members.length
                ? members.slice(0, 6).map((e) => `<tr class="click" data-id="${e.id}"><td class="nm">${esc(e.name)}${e.is_leader ? '<span class="sp-tag lead">LEAD</span>' : ''}${!e.active ? '<span class="sp-tag inactive">INACTIVE</span>' : ''}</td>
                    <td class="num">${n(e.stage_1)}</td><td class="num">${e.contact_rate}%</td><td class="num">${n(e.bookings)}</td><td class="num">${n(e.confirmed_sales)}</td></tr>`).join('')
                : '<tr><td colspan="5" class="sp-empty">No members</td></tr>';
            $$('#spTopExec tbody tr[data-id]').forEach((tr) => tr.addEventListener('click', () => showExec(Number(tr.dataset.id))));
            const allLink = $('#spTopExecAll');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'executive');
            if (t.team_id) url.searchParams.set('team_id', t.team_id);
            url.searchParams.delete('focus_team');
            allLink.href = url.toString();
            allLink.textContent = `View all ${members.length} executives →`;

            const items = [];
            if (t.uncontacted) items.push(['fa-phone', '', `${n(t.uncontacted)} uncontacted leads`, `${pct(t.uncontacted, t.stage_1)} of this period's leads not contacted`]);
            if (t.overdue) items.push(['fa-clock-rotate-left', 'red', `${n(t.overdue)} overdue follow-ups`, 'Callbacks past their date']);
            if (t.backlog_uncontacted) items.push(['fa-hourglass-half', '', `${n(t.backlog_uncontacted)} untouched open leads`, 'Assigned > 24h ago, never contacted']);
            if (t.target_bookings && t.bookings < t.target_bookings) items.push(['fa-bullseye-arrow', '', `${n(t.target_bookings - t.bookings)} units to reach target`, `${n(t.bookings)} of ${n(t.target_bookings)} booked`]);
            if (t.bookings > t.confirmed_sales) items.push(['fa-file-invoice-dollar', '', `${n(t.bookings - t.confirmed_sales)} bookings unverified`, 'Awaiting accounts payment verification']);
            if (!t.target_bookings && t.is_real_team) items.push(['fa-circle-info', 'green', 'No booking target set', 'Set targets for members in Sales Dashboard']);
            $('#spTeamAttnTitle').textContent = `${t.name} — Needs Attention`;
            $('#spTeamAttnCount').textContent = items.length;
            $('#spTeamAttn').innerHTML = items.length
                ? items.map((a) => `<div class="sp-attn"><div class="ic ${a[1]}"><i class="fa-regular ${a[0]}"></i></div><div><div class="t">${a[2]}</div><div class="d">${a[3]}</div></div></div>`).join('')
                : '<div class="sp-empty"><i class="fa-regular fa-circle-check" style="color:var(--sp-green)"></i> Nothing urgent.</div>';
        }

        $('#spTeamSort').addEventListener('change', (e) => { sortKey = e.target.value; drawTeams(); });
        drawTeams();
        if (selectedKey) selectTeam(selectedKey);
        if (D.focusTeam) document.getElementById('spFunnelTeam').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ---------- card template helper ----------
    function renderExecCard(e) {
        const isInactive = !e.active;
        let statusClass = 'active';
        let statusLabel = 'Active';
        if (e.employee_status === 'resigned') {
            statusClass = 'resigned';
            statusLabel = 'Resigned';
        } else if (e.employee_status === 'legacy') {
            statusClass = 'legacy';
            statusLabel = 'Legacy Archive';
        } else if (e.employee_status === 'unlinked') {
            statusClass = 'unlinked';
            statusLabel = 'Unlinked';
        } else if (isInactive) {
            statusClass = 'inactive';
            statusLabel = 'Inactive';
        }

        return `
            <div class="sp-exec-card click" data-id="${e.id}">
                <div class="sp-exec-card-head">
                    <div class="sp-exec-card-user">
                        <span class="sp-exec-card-avatar ${statusClass}">${esc(initials(e.name))}</span>
                        <div style="min-width:0">
                            <div class="sp-exec-card-name" title="${esc(e.name)}">
                                ${esc(e.name)}
                                ${e.is_leader ? '<span class="sp-tag lead">LEAD</span>' : ''}
                            </div>
                            <div class="sp-exec-card-meta">${esc(e.role)} · ${esc(e.team_name)}</div>
                        </div>
                    </div>
                    <span class="sp-status-badge ${statusClass}">${esc(statusLabel)}</span>
                </div>
                <div class="sp-exec-card-stats">
                    <div class="sp-exec-stat-box">
                        <div class="k"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                        <div class="v">${n(e.stage_1)}</div>
                        <div class="s">${e.stage_1 ? e.contact_rate + '% contacted' : '0%'}</div>
                    </div>
                    <div class="sp-exec-stat-box">
                        <div class="k"><i class="fa-regular fa-headset"></i> Follow-ups</div>
                        <div class="v">${n(e.follow_ups)}</div>
                        <div class="s">${n(e.leads_followed)} leads called</div>
                    </div>
                    <div class="sp-exec-stat-box">
                        <div class="k"><i class="fa-regular fa-location-dot"></i> Visits Done</div>
                        <div class="v">${n(e.visits_done)}</div>
                        <div class="s">${e.upcoming_visits ? n(e.upcoming_visits) + ' upcoming' : '0 upcoming'}</div>
                    </div>
                    <div class="sp-exec-stat-box highlight">
                        <div class="k"><i class="fa-regular fa-calendar-check"></i> Bookings</div>
                        <div class="v">${n(e.bookings)}</div>
                        <div class="s">${tk(e.booking_value)} · ${n(e.confirmed_sales)} conf</div>
                    </div>
                </div>
                <div class="sp-exec-card-foot">
                    <span>
                        ${e.overdue > 0 ? `<b style="color:var(--sp-amber)">${n(e.overdue)} overdue</b>` : '<span style="color:var(--sp-green)"><i class="fa-regular fa-check"></i> No overdue</span>'}
                    </span>
                    <span style="color:var(--sp-green);font-weight:600">View funnel &amp; details →</span>
                </div>
            </div>`;
    }

    // ---------- executive tab ----------
    const execTable = $('#spExecTable');
    const execCards = $('#spExecCards');
    let execSort = { k: 'confirmed_sales', dir: -1 };

    function drawExecs() {
        if (!D.executives) return;
        const q = ($('#spExecSearch') ? $('#spExecSearch').value : '').toLowerCase().trim();
        const team = $('#spExecTeam') ? $('#spExecTeam').value : '';
        const sortSel = $('#spExecSort');
        const k = sortSel ? sortSel.value : execSort.k;
        const hideZero = $('#spExecZero') ? $('#spExecZero').checked : false;

        let list = D.executives.filter((e) => {
            if (!e.is_sales || !e.active) return false;
            if (team && e.team_key !== team) return false;
            if (q && !(e.name.toLowerCase().includes(q) || e.team_name.toLowerCase().includes(q))) return false;
            if (hideZero && !(e.stage_1 || e.follow_ups || e.bookings)) return false;
            return true;
        });

        list.sort((a, b) => {
            let x = a[k], y = b[k];
            if (k === 'name') return execSort.dir * String(x).localeCompare(String(y));
            x = x === null || x === undefined ? -Infinity : x;
            y = y === null || y === undefined ? -Infinity : y;
            return (y - x) || b.stage_1 - a.stage_1;
        });

        if (execCards) {
            execCards.innerHTML = list.length
                ? list.map(renderExecCard).join('')
                : '<div class="sp-empty" style="grid-column:1/-1">No active executives match the filters.</div>';
            $$('.sp-exec-card[data-id]', execCards).forEach((c) => c.addEventListener('click', () => showExec(Number(c.dataset.id))));
        }

        if (execTable) {
            $('tbody', execTable).innerHTML = list.length
                ? list.map((e) => `
                <tr class="click" data-id="${e.id}">
                    <td><div class="sp-who"><span class="sp-avatar">${esc(initials(e.name))}</span><div class="nm">${esc(e.name)}${e.is_leader ? '<span class="sp-tag lead">LEAD</span>' : ''}<small>${esc(e.role)} · ${esc(e.team_name)}</small></div></div></td>
                    <td class="num">${n(e.stage_1)}</td>
                    <td class="num">${e.stage_1 ? e.contact_rate + '%' : '—'}<div style="font-size:10.5px;color:var(--sp-soft)">${e.uncontacted ? n(e.uncontacted) + ' pending' : ''}</div></td>
                    <td class="num" style="${e.median_response_hours > 24 ? 'color:var(--sp-amber)' : ''}">${hrs(e.median_response_hours)}</td>
                    <td class="num">${n(e.follow_ups)}</td>
                    <td class="num" style="${e.overdue > 0 ? 'color:var(--sp-amber);font-weight:600' : ''}">${n(e.overdue)}</td>
                    <td class="num" style="${e.backlog_uncontacted > 0 ? 'color:var(--sp-red)' : ''}">${n(e.backlog_uncontacted)}</td>
                    <td class="num">${n(e.visits_done)}</td>
                    <td class="num">${n(e.bookings)}</td>
                    <td class="num">${tk(e.booking_value)}</td>
                    <td class="num"><b>${n(e.confirmed_sales)}</b></td>
                    <td class="num">${tk(e.collection)}</td>
                    <td class="num">${e.target_achievement === null ? '<span style="color:var(--sp-soft)">—</span>' : e.target_achievement + '%'}</td>
                </tr>`).join('')
                : '<tr><td colspan="13" class="sp-empty">No active executives match the filters.</td></tr>';
            $$('tbody tr[data-id]', execTable).forEach((tr) => tr.addEventListener('click', () => showExec(Number(tr.dataset.id))));
        }

        const countEl = $('#spExecCount');
        if (countEl) countEl.textContent = `${list.length} active executives`;
    }

    if (execTable || execCards) {
        ['#spExecSearch', '#spExecTeam', '#spExecSort', '#spExecZero'].forEach((s) => {
            const el = $(s);
            if (el) el.addEventListener('input', drawExecs);
        });
        const execViewToggle = $('#spExecViewToggle');
        if (execViewToggle) {
            $$('button', execViewToggle).forEach((btn) => {
                btn.addEventListener('click', () => {
                    $$('button', execViewToggle).forEach((b) => b.classList.toggle('on', b === btn));
                    const view = btn.dataset.view;
                    const cardsEl = $('#spExecCards');
                    const tableWrap = $('#spExecTableWrap');
                    if (view === 'cards') {
                        if (cardsEl) cardsEl.style.display = 'grid';
                        if (tableWrap) tableWrap.style.display = 'none';
                    } else {
                        if (cardsEl) cardsEl.style.display = 'none';
                        if (tableWrap) tableWrap.style.display = 'block';
                    }
                });
            });
        }
        drawExecs();
    }

    // ---------- inactive executive tab ----------
    const inactTable = $('#spInactiveExecTable');
    const inactCards = $('#spInactiveExecCards');
    let inactSort = { k: 'confirmed_sales', dir: -1 };

    function drawInactiveExecs() {
        if (!D.inactiveExecutives) return;
        const q = ($('#spInactiveExecSearch') ? $('#spInactiveExecSearch').value : '').toLowerCase().trim();
        const team = $('#spInactiveExecTeam') ? $('#spInactiveExecTeam').value : '';
        const status = $('#spInactiveExecStatus') ? $('#spInactiveExecStatus').value : '';
        const sortSel = $('#spInactiveExecSort');
        const k = sortSel ? sortSel.value : inactSort.k;
        const hideZero = $('#spInactiveExecZero') ? $('#spInactiveExecZero').checked : false;

        let list = D.inactiveExecutives.filter((e) => {
            if (!e.is_sales || e.active) return false;
            if (team && e.team_key !== team) return false;
            if (status && e.employee_status !== status) return false;
            if (q && !(e.name.toLowerCase().includes(q) || e.team_name.toLowerCase().includes(q))) return false;
            if (hideZero && !(e.stage_1 || e.follow_ups || e.bookings)) return false;
            return true;
        });

        list.sort((a, b) => {
            let x = a[k], y = b[k];
            if (k === 'name') return inactSort.dir * String(x).localeCompare(String(y));
            x = x === null || x === undefined ? -Infinity : x;
            y = y === null || y === undefined ? -Infinity : y;
            return (y - x) || b.stage_1 - a.stage_1;
        });

        if (inactCards) {
            inactCards.innerHTML = list.length
                ? list.map(renderExecCard).join('')
                : '<div class="sp-empty" style="grid-column:1/-1">No inactive executives match the filters.</div>';
            $$('.sp-exec-card[data-id]', inactCards).forEach((c) => c.addEventListener('click', () => showExec(Number(c.dataset.id))));
        }

        if (inactTable) {
            $('tbody', inactTable).innerHTML = list.length
                ? list.map((e) => {
                    let statusClass = 'inactive';
                    let statusLabel = 'Inactive';
                    if (e.employee_status === 'resigned') {
                        statusClass = 'resigned';
                        statusLabel = 'Resigned';
                    } else if (e.employee_status === 'legacy') {
                        statusClass = 'legacy';
                        statusLabel = 'Legacy Archive';
                    } else if (e.employee_status === 'unlinked') {
                        statusClass = 'unlinked';
                        statusLabel = 'Unlinked';
                    }
                    return `
                    <tr class="click muted" data-id="${e.id}">
                        <td><div class="sp-who"><span class="sp-avatar ${statusClass}">${esc(initials(e.name))}</span><div class="nm">${esc(e.name)}<small>${esc(e.role)} · ${esc(e.team_name)}</small></div></div></td>
                        <td><span class="sp-status-badge ${statusClass}">${esc(statusLabel)}</span></td>
                        <td class="num">${n(e.stage_1)}</td>
                        <td class="num">${e.stage_1 ? e.contact_rate + '%' : '—'}</td>
                        <td class="num">${n(e.follow_ups)}</td>
                        <td class="num">${n(e.visits_done)}</td>
                        <td class="num">${n(e.bookings)}</td>
                        <td class="num">${tk(e.booking_value)}</td>
                        <td class="num"><b>${n(e.confirmed_sales)}</b></td>
                        <td class="num">${tk(e.collection)}</td>
                    </tr>`;
                }).join('')
                : '<tr><td colspan="10" class="sp-empty">No inactive executives match the filters.</td></tr>';
            $$('tbody tr[data-id]', inactTable).forEach((tr) => tr.addEventListener('click', () => showExec(Number(tr.dataset.id))));
        }

        const countEl = $('#spInactiveExecCount');
        if (countEl) countEl.textContent = `${list.length} inactive executives`;
    }

    if (inactTable || inactCards) {
        ['#spInactiveExecSearch', '#spInactiveExecTeam', '#spInactiveExecStatus', '#spInactiveExecSort', '#spInactiveExecZero'].forEach((s) => {
            const el = $(s);
            if (el) el.addEventListener('input', drawInactiveExecs);
        });
        const inactViewToggle = $('#spInactiveExecViewToggle');
        if (inactViewToggle) {
            $$('button', inactViewToggle).forEach((btn) => {
                btn.addEventListener('click', () => {
                    $$('button', inactViewToggle).forEach((b) => b.classList.toggle('on', b === btn));
                    const view = btn.dataset.view;
                    const cardsEl = $('#spInactiveExecCards');
                    const tableWrap = $('#spInactiveExecTableWrap');
                    if (view === 'cards') {
                        if (cardsEl) cardsEl.style.display = 'grid';
                        if (tableWrap) tableWrap.style.display = 'none';
                    } else {
                        if (cardsEl) cardsEl.style.display = 'none';
                        if (tableWrap) tableWrap.style.display = 'block';
                    }
                });
            });
        }
        drawInactiveExecs();
    }

    function showExec(id) {
        const e = (D.executives && D.executives.find((x) => x.id === id))
               || (D.inactiveExecutives && D.inactiveExecutives.find((x) => x.id === id));
        if (!e) return;
        const mini = (k, v, s) => `<div class="sp-mini"><div class="k">${k}</div><div class="v">${v}</div><div class="s">${s || ''}</div></div>`;
        const html = `
            <div class="sp-mini-grid" style="grid-template-columns:repeat(3,1fr)">
                ${mini('Leads received', n(e.stage_1), `${n(e.uncontacted)} not contacted`)}
                ${mini('Contact rate', e.contact_rate + '%', `${e.contacted_24h_rate ?? 0}% within 24h`)}
                ${mini('1st response', hrs(e.median_response_hours), 'median')}
                ${mini('Follow-ups', n(e.follow_ups), `${n(e.leads_followed)} leads`)}
                ${mini('Overdue', n(e.overdue), `${n(e.due_today)} due today`)}
                ${mini('Untouched', n(e.backlog_uncontacted), 'open > 24h')}
                ${mini('Visits done', n(e.visits_done), `${n(e.upcoming_visits)} upcoming`)}
                ${mini('Bookings', n(e.bookings), tk(e.booking_value))}
                ${mini('Confirmed', n(e.confirmed_sales), tk(e.confirmed_value))}
                ${mini('Collection', tk(e.collection), 'verified')}
                ${mini('Onboarded', n(e.onboarded), 'this period')}
                ${mini('Target', e.target_bookings ? `${n(e.bookings)}/${n(e.target_bookings)}` : '—', e.target_achievement !== null ? e.target_achievement + '% achieved' : 'not set')}
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin:22px 0 8px">
                <h3 style="font-size:14.5px;font-weight:700;margin:0">Funnel (leads received this period)</h3>
                <div class="sp-toggle" data-funnel-toggle="spFunnelExec"><button type="button" class="on" data-mode="overall">Overall</button><button type="button" data-mode="step">Step-wise</button></div>
            </div>
            <div id="spFunnelExec"></div>`;
        const statusBadge = !e.active ? ` · <span class="sp-status-badge ${e.employee_status || 'inactive'}">${esc(e.employee_status || 'Inactive')}</span>` : '';
        openDrawer(e.name, `${esc(e.role)} · ${esc(e.team_name)}${statusBadge}`, html);
        renderFunnel($('#spFunnelExec'), e, 'overall', 5);
        const tg = $('[data-funnel-toggle="spFunnelExec"]');
        $$('button', tg).forEach((b) =>
            b.addEventListener('click', () => {
                $$('button', tg).forEach((x) => x.classList.toggle('on', x === b));
                const st = funnelState.spFunnelExec;
                renderFunnel($('#spFunnelExec'), st.m, b.dataset.mode, st.selected);
            })
        );
    }

    // ---------- filters: keep executive list in sync with team ----------
    const teamSel = $('#spTeam'), execSel = $('#spExec');
    if (teamSel && execSel) {
        const syncExec = () => {
            const t = teamSel.value;
            $$('option', execSel).forEach((o) => {
                if (!o.value) return;
                o.hidden = !!t && o.dataset.team !== t;
            });
            if (execSel.selectedOptions[0] && execSel.selectedOptions[0].hidden) execSel.value = '';
        };
        teamSel.addEventListener('change', syncExec);
        syncExec();
    }
    // Custom range: require both dates
    const form = $('#spFilterForm');
    form && form.addEventListener('submit', (ev) => {
        const f = $('#spFrom').value, t = $('#spTo').value;
        if ((f && !t) || (!f && t)) {
            ev.preventDefault();
            alert('Please select both start and end dates for a custom range, or clear both to use the sales cycle.');
        } else if (f && t && f > t) {
            ev.preventDefault();
            alert('Start date must be before end date.');
        }
        $$('select, input', form).forEach((el) => { if (!el.value && el.name !== 'tab') el.disabled = true; });
    });
})();
