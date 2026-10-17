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
        mode = 'overall';
        selected = selected || 5;
        funnelState[el.id] = { m, mode, selected };
        const base = m.stage_1 || 0;
        let rows = '';
        STAGE_KEYS.forEach((s) => {
            const v = m['stage_' + s] || 0;
            const p = base ? (v / base) * 100 : 0;
            rows += `
                <div class="sp-funnel-row ${s === selected ? 'sel' : ''}" data-stage="${s}">
                    <span class="no">${String(s).padStart(2, '0')}</span>
                    <div><div class="nm">${esc(STAGES[s])}</div><div class="sp-bar"><span style="width:${Math.max(p, v ? 0.6 : 0).toFixed(2)}%"></span></div></div>
                    <span class="ct">${n(v)}</span>
                    <span class="pc">${p.toFixed(1)}%</span>
                </div>`;
            if (s === 2) {
                const uncontacted = m.uncontacted !== undefined ? m.uncontacted : Math.max(0, base - (m.stage_2 || 0));
                const uncontactedPct = base ? (uncontacted / base) * 100 : 0;
                rows += `
                    <div class="sp-funnel-row uncontacted-row" data-stage="uncontacted" style="margin:2px 0;">
                        <span class="no" style="color:#ef4444;font-weight:700">--</span>
                        <div>
                            <div class="nm" style="color:#b91c1c;font-weight:600">Not Contacted <small style="color:#ef4444;font-size:11px;font-weight:400">(Awaiting 1st contact)</small></div>
                            <div class="sp-bar red"><span style="width:${Math.max(uncontactedPct, uncontacted ? 0.6 : 0).toFixed(2)}%"></span></div>
                        </div>
                        <span class="ct" style="color:#b91c1c;font-weight:700">${n(uncontacted)}</span>
                        <span class="pc" style="color:#b91c1c;font-weight:700">${uncontactedPct.toFixed(1)}%</span>
                    </div>`;
            }
        });
        const sel = m['stage_' + selected] || 0;
        rows += `
            <div class="sp-funnel-foot">
                <div><div class="k">Selected stage</div><div class="v">${n(sel)}</div><div class="s">${esc(STAGES[selected] || '')} · ${pct(sel, base)}</div></div>
                <div><div class="k">Lead to booking</div><div class="v">${pct(m.stage_5, base, 2)}</div><div class="s">${n(m.stage_5)} / ${n(base)}</div></div>
                <div><div class="k">Visit to booking</div><div class="v">${pct(m.stage_5, m.stage_4)}</div><div class="s">${n(m.stage_5)} / ${n(m.stage_4)}</div></div>
            </div>`;
        el.innerHTML = rows;
        $$('.sp-funnel-row', el).forEach((r) => {
            if (r.dataset.stage && r.dataset.stage !== 'uncontacted') {
                r.addEventListener('click', () => renderFunnel(el, m, 'overall', Number(r.dataset.stage)));
            }
        });
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

        let memberSortKey = 'bookings';
        const memberSortSelect = $('#spMemberSort');
        if (memberSortSelect) {
            memberSortSelect.addEventListener('change', (e) => {
                memberSortKey = e.target.value;
                if (selectedKey) selectTeam(selectedKey);
            });
        }

        function selectTeam(key) {
            selectedKey = key;
            $$('tbody tr', teamTable).forEach((tr) => tr.classList.toggle('sel', tr.dataset.key === key));
            const t = D.teams.find((x) => x.key === key);
            if (!t) return;
            $('#spTeamFunnelTitle').textContent = `${t.name} — Sales Funnel`;
            $('#spTeamFunnelSub').textContent = `${t.member_count} members · ${n(t.stage_1)} leads received`;
            renderFunnel($('#spFunnelTeam'), t, 'overall', 5);

            // Unassigned leads waiting with Team Leader
            const unassignedPeriod = t.unassigned_by_leader || 0;
            const unassignedTotal = t.unassigned_by_leader_total || 0;
            const unalert = $('#spTeamUnassignedAlert');
            if (unalert) {
                if (unassignedPeriod > 0 || unassignedTotal > 0) {
                    unalert.style.display = 'block';
                    unalert.innerHTML = `
                        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:9px 13px;display:flex;align-items:center;justify-content:space-between;gap:12px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <i class="fa-solid fa-user-clock" style="color:#d97706;font-size:16px"></i>
                                <div>
                                    <div style="font-weight:700;color:#92400e;font-size:12.5px">${n(unassignedPeriod)} Leads Not Yet Assigned by Team Leader</div>
                                    <div style="font-size:11px;color:#b45309;margin-top:1px">Received in this period under this team, but Team Leader has not yet distributed to any executive (${n(unassignedTotal)} total unassigned).</div>
                                </div>
                            </div>
                            <span class="sp-count-chip" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-weight:700;font-size:12.5px">${n(unassignedPeriod)}</span>
                        </div>`;
                } else {
                    unalert.style.display = 'none';
                    unalert.innerHTML = '';
                }
            }

            // Member pool: combine all_executives and match by team_key OR t.members
            const pool = (D.all_executives && D.all_executives.length)
                ? D.all_executives
                : [ ...(D.executives || []), ...(D.inactiveExecutives || []) ];
            const memberIdSet = new Set((t.members || []).map(Number));
            const rawMembers = pool.filter((e) => e.team_key === key || memberIdSet.has(Number(e.id)));

            // Deduplicate by ID
            const seen = new Set();
            const members = [];
            for (const m of rawMembers) {
                const mid = Number(m.id);
                if (!seen.has(mid)) {
                    seen.add(mid);
                    members.push(m);
                }
            }

            // Sort members
            const sortedMembers = [...members].sort((a, b) => {
                if (memberSortKey === 'stage_2') {
                    return (b.stage_2 - a.stage_2) || (b.contact_rate - a.contact_rate) || (b.bookings - a.bookings) || (b.stage_1 - a.stage_1);
                }
                if (memberSortKey === 'stage_1') {
                    return (b.stage_1 - a.stage_1) || (b.stage_2 - a.stage_2) || (b.bookings - a.bookings);
                }
                if (memberSortKey === 'uncontacted') {
                    const ua = a.uncontacted !== undefined ? a.uncontacted : Math.max(0, (a.stage_1 || 0) - (a.stage_2 || 0));
                    const ub = b.uncontacted !== undefined ? b.uncontacted : Math.max(0, (b.stage_1 || 0) - (b.stage_2 || 0));
                    return (ub - ua) || (b.stage_1 - a.stage_1);
                }
                if (memberSortKey === 'follow_ups') {
                    return (b.follow_ups - a.follow_ups) || (b.bookings - a.bookings) || (b.stage_1 - a.stage_1);
                }
                if (memberSortKey === 'visits_done') {
                    return (b.visits_done - a.visits_done) || (b.bookings - a.bookings) || (b.stage_1 - a.stage_1);
                }
                if (memberSortKey === 'confirmed_sales') {
                    return (b.confirmed_sales - a.confirmed_sales) || (b.bookings - a.bookings) || (b.booking_value - a.booking_value);
                }
                if (memberSortKey === 'booking_value') {
                    return (b.booking_value - a.booking_value) || (b.bookings - a.bookings);
                }
                return (b.bookings - a.bookings) || (b.confirmed_sales - a.confirmed_sales) || (b.stage_2 - a.stage_2) || (b.stage_1 - a.stage_1);
            });

            const mTitle = $('#spTeamMembersTitle');
            if (mTitle) mTitle.textContent = `${t.name} — Members Performance`;
            const mSub = $('#spTeamMembersSub');
            if (mSub) mSub.textContent = `${members.length} members · ${n(t.stage_1)} leads · ${n(t.bookings)} bookings`;

            const tbody = $('#spTeamMembersTable tbody');
            const tfoot = $('#spTeamMembersTable tfoot');

            if (tbody) {
                let rowsHtml = '';
                if (sortedMembers.length === 0 && unassignedPeriod === 0) {
                    rowsHtml = '<tr><td colspan="10" class="sp-empty">No members or activity in this team.</td></tr>';
                } else {
                    sortedMembers.forEach((e, idx) => {
                        const isInactive = !e.active;
                        const uncontacted = e.uncontacted !== undefined ? e.uncontacted : Math.max(0, (e.stage_1 || 0) - (e.stage_2 || 0));
                        rowsHtml += `
                            <tr class="click" data-id="${e.id}" style="${isInactive ? 'opacity:0.75' : ''}">
                                <td style="color:var(--sp-soft);font-size:11px">${idx + 1}</td>
                                <td class="nm">
                                    <span style="font-weight:600">${esc(e.name)}</span>
                                    ${e.is_leader ? '<span class="sp-tag lead" style="margin-left:4px">LEAD</span>' : ''}
                                    ${isInactive ? '<span class="sp-tag inactive" style="margin-left:4px">INACTIVE</span>' : ''}
                                    ${e.role ? `<small style="display:block;font-size:10.5px;color:var(--sp-muted)">${esc(e.role)}</small>` : ''}
                                </td>
                                <td class="num" style="font-weight:600">${n(e.stage_1)}</td>
                                <td class="num">
                                    <span style="font-weight:600">${n(e.stage_2)}</span>
                                    <small style="color:var(--sp-muted);margin-left:2px">(${e.contact_rate}%)</small>
                                </td>
                                <td class="num" style="${uncontacted > 0 ? 'color:#dc2626;font-weight:700' : 'color:var(--sp-soft)'}">
                                    ${n(uncontacted)}
                                </td>
                                <td class="num" style="font-weight:600">${n(e.follow_ups)}</td>
                                <td class="num" style="font-weight:600">${n(e.visits_done)}</td>
                                <td class="num" style="font-weight:700;color:var(--sp-green);font-size:13.5px">
                                    ${n(e.bookings)}
                                </td>
                                <td class="num" style="font-weight:600">${n(e.confirmed_sales)}</td>
                                <td class="num">${tk(e.booking_value)}</td>
                            </tr>`;
                    });

                    // Row for unassigned leads sitting with Team Leader
                    if (unassignedPeriod > 0) {
                        rowsHtml += `
                            <tr style="background:#fffcf5;border-top:1px dashed #fde68a">
                                <td style="color:#d97706"><i class="fa-solid fa-clock-rotate-left"></i></td>
                                <td class="nm" style="color:#92400e">
                                    <span style="font-weight:700">Unassigned by Team Leader</span>
                                    <small style="color:#b45309;display:block">Pending distribution to executives</small>
                                </td>
                                <td class="num" style="color:#92400e;font-weight:700">${n(unassignedPeriod)}</td>
                                <td class="num" style="color:var(--sp-soft)">0 (0%)</td>
                                <td class="num" style="color:#dc2626;font-weight:700">${n(unassignedPeriod)}</td>
                                <td class="num" style="color:var(--sp-soft)">0</td>
                                <td class="num" style="color:var(--sp-soft)">0</td>
                                <td class="num" style="color:var(--sp-soft)">0</td>
                                <td class="num" style="color:var(--sp-soft)">0</td>
                                <td class="num" style="color:var(--sp-soft)">৳0</td>
                            </tr>`;
                    }
                }
                tbody.innerHTML = rowsHtml;

                $$('#spTeamMembersTable tbody tr[data-id]').forEach((tr) =>
                    tr.addEventListener('click', () => showExec(Number(tr.dataset.id)))
                );
            }

            if (tfoot) {
                const sumLeads = members.reduce((s, m) => s + (m.stage_1 || 0), 0) + unassignedPeriod;
                const sumContacted = members.reduce((s, m) => s + (m.stage_2 || 0), 0);
                const sumUncontacted = members.reduce((s, m) => s + (m.uncontacted !== undefined ? m.uncontacted : Math.max(0, (m.stage_1 || 0) - (m.stage_2 || 0))), 0) + unassignedPeriod;
                const sumFollowUps = members.reduce((s, m) => s + (m.follow_ups || 0), 0);
                const sumVisits = members.reduce((s, m) => s + (m.visits_done || 0), 0);
                const sumBookings = members.reduce((s, m) => s + (m.bookings || 0), 0);
                const sumConfirmed = members.reduce((s, m) => s + (m.confirmed_sales || 0), 0);
                const sumValue = members.reduce((s, m) => s + (m.booking_value || 0), 0);

                tfoot.innerHTML = `
                    <tr style="font-weight:700;border-top:2px solid var(--sp-border)">
                        <td></td>
                        <td>Total</td>
                        <td class="num">${n(sumLeads)}</td>
                        <td class="num">${n(sumContacted)} <small style="color:var(--sp-muted);font-weight:normal">(${t.contact_rate}%)</small></td>
                        <td class="num" style="${sumUncontacted > 0 ? 'color:#dc2626' : ''}">${n(sumUncontacted)}</td>
                        <td class="num">${n(sumFollowUps)}</td>
                        <td class="num">${n(sumVisits)}</td>
                        <td class="num" style="color:var(--sp-green);font-size:14px">${n(sumBookings)}</td>
                        <td class="num">${n(sumConfirmed)}</td>
                        <td class="num">${tk(sumValue)}</td>
                    </tr>`;
            }

            const allLink = $('#spTopExecAll');
            if (allLink) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'executive');
                if (t.team_id) url.searchParams.set('team_id', t.team_id);
                url.searchParams.delete('focus_team');
                allLink.href = url.toString();
                allLink.textContent = `View all ${members.length} members in Executive tab →`;
            }

            const items = [];
            if (unassignedPeriod > 0) items.push(['fa-user-clock', 'red', `${n(unassignedPeriod)} unassigned leads in team`, 'Team leader has not assigned these to any executive yet']);
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
        const e = (D.all_executives && D.all_executives.find((x) => x.id === id))
               || (D.executives && D.executives.find((x) => x.id === id))
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

    // ---------- searchable select2 filters ----------
    function initSelect2Filters() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            setTimeout(initSelect2Filters, 50);
            return;
        }
        const $jq = window.jQuery;

        const select2BaseConfig = {
            width: '100%',
            dropdownCssClass: 'sp-select2-dropdown',
            language: {
                noResults: () => 'No matches found'
            }
        };

        const $project = $jq('#spProject');
        const $team = $jq('#spTeam');
        const $exec = $jq('#spExec');

        if ($project.length) {
            $project.select2(Object.assign({}, select2BaseConfig, {
                placeholder: 'All projects'
            }));
        }

        if ($team.length) {
            $team.select2(Object.assign({}, select2BaseConfig, {
                placeholder: 'All teams'
            }));
        }

        if ($exec.length) {
            $exec.select2(Object.assign({}, select2BaseConfig, {
                placeholder: 'All executives'
            }));
        }

        // Sync Executive list with Team selection
        if ($team.length && $exec.length) {
            const syncExecOptions = () => {
                const selectedTeam = $team.val();
                const currentExec = $exec.val();
                let isCurrentValid = false;

                $exec.find('option').each(function () {
                    const $opt = $jq(this);
                    const optVal = $opt.val();
                    const optTeam = $opt.attr('data-team');

                    if (!optVal) {
                        $opt.prop('disabled', false); // "All executives" option
                        $opt.prop('hidden', false);
                        return;
                    }

                    const isMatch = !selectedTeam || optTeam === selectedTeam;
                    $opt.prop('disabled', !isMatch);
                    $opt.prop('hidden', !isMatch);

                    if (optVal === currentExec && isMatch) {
                        isCurrentValid = true;
                    }
                });

                if (currentExec && !isCurrentValid) {
                    $exec.val('').trigger('change.select2');
                } else {
                    $exec.trigger('change.select2');
                }
            };

            $team.on('change', syncExecOptions);
            if ($team.val()) {
                syncExecOptions();
            }
        }
    }

    if (document.readyState === 'complete') {
        initSelect2Filters();
    } else {
        window.addEventListener('load', initSelect2Filters);
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
