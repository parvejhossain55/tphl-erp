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
        const extraScope = m.id ? `data-exec-id="${m.id}"` : (m.key ? `data-team-key="${esc(m.key)}"` : '');
        let rows = '';
        STAGE_KEYS.forEach((s) => {
            let v = m['stage_' + s] || 0;
            const p = base ? (v / base) * 100 : 0;
            let displayCount = n(v);
            let stageLabel = esc(STAGES[s]);

            // Serial numbering for 7 funnel rows:
            // 1 -> 01 (Received)
            // 2 -> 02 (Contacted)
            // [no_response] -> 03 (No Response)
            // [uncontacted] -> 04 (Not Contacted)
            // 3 -> 05 (Visit Scheduled)
            // 4 -> 06 (Visited)
            // 5 -> 07 (Booked)
            let serialNo = '0' + s;
            if (s === 3) serialNo = '05';
            else if (s === 4) serialNo = '06';
            else if (s === 5) serialNo = '07';

            if (s === 5) {
                const totalUnits = (m.bookings !== undefined) ? m.bookings : v;
                displayCount = n(totalUnits);
                stageLabel = `${esc(STAGES[s])} <small style="font-size:11px;font-weight:500;color:var(--sp-muted);margin-left:4px">(${n(totalUnits)} units sold by ${n(v)} clients)</small>`;
            }

            rows += `
                <div class="sp-funnel-row click ${String(s) === String(selected) ? 'sel' : ''}" data-stage="${s}" data-gap="stage_${s}" ${extraScope} style="cursor:pointer" title="Click to view Stage ${serialNo} (${esc(STAGES[s])}) leads">
                    <span class="no">${serialNo}</span>
                    <div><div class="nm">${stageLabel} <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;opacity:0.6;margin-left:2px"></i></div><div class="sp-bar"><span style="width:${Math.max(p, v ? 0.6 : 0).toFixed(2)}%"></span></div></div>
                    <span class="ct">${displayCount}</span>
                    <span class="pc">${p.toFixed(1)}%</span>
                </div>`;

            if (s === 2) {
                const noResponse = m.no_response || 0;
                const noResponsePct = base ? (noResponse / base) * 100 : 0;
                rows += `
                    <div class="sp-funnel-row no-response-row click ${selected === 'no_response' ? 'sel' : ''}" data-stage="no_response" data-gap="no_response_leads" ${extraScope} style="margin:2px 0;cursor:pointer;" title="Click to view Stage 03 (No Response) leads">
                        <span class="no">03</span>
                        <div>
                            <div class="nm" style="font-weight:600">No Response <small style="font-size:11px;font-weight:400">(Unreachable / Not Received)</small> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;opacity:0.6;margin-left:2px"></i></div>
                            <div class="sp-bar amber"><span style="width:${Math.max(noResponsePct, noResponse ? 0.6 : 0).toFixed(2)}%"></span></div>
                        </div>
                        <span class="ct" style="font-weight:700">${n(noResponse)}</span>
                        <span class="pc" style="font-weight:700">${noResponsePct.toFixed(1)}%</span>
                    </div>`;

                const uncontacted = m.uncontacted !== undefined ? m.uncontacted : Math.max(0, base - (m.stage_2 || 0));
                const uncontactedPct = base ? (uncontacted / base) * 100 : 0;
                rows += `
                    <div class="sp-funnel-row uncontacted-row click ${selected === 'uncontacted' ? 'sel' : ''}" data-stage="uncontacted" data-gap="uncontacted" ${extraScope} style="margin:2px 0;cursor:pointer;" title="Click to view Stage 04 (Not Contacted) leads">
                        <span class="no">04</span>
                        <div>
                            <div class="nm" style="font-weight:600">Not Contacted <small style="font-size:11px;font-weight:400">(Awaiting 1st contact)</small> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;opacity:0.6;margin-left:2px"></i></div>
                            <div class="sp-bar red"><span style="width:${Math.max(uncontactedPct, uncontacted ? 0.6 : 0).toFixed(2)}%"></span></div>
                        </div>
                        <span class="ct" style="font-weight:700">${n(uncontacted)}</span>
                        <span class="pc" style="font-weight:700">${uncontactedPct.toFixed(1)}%</span>
                    </div>`;
            }
        });
        let sel = 0;
        let selSub = '';
        if (selected === 'no_response') {
            sel = m.no_response || 0;
            selSub = `No Response · ${pct(sel, base)}`;
        } else if (selected === 'uncontacted') {
            sel = m.uncontacted !== undefined ? m.uncontacted : Math.max(0, base - (m.stage_2 || 0));
            selSub = `Not Contacted · ${pct(sel, base)}`;
        } else if (String(selected) === '5') {
            sel = m.bookings || m.stage_5 || 0;
            selSub = `Booked · ${n(sel)} units sold by ${n(m.stage_5 || 0)} clients`;
        } else {
            const numSt = Number(selected);
            sel = m['stage_' + numSt] || 0;
            selSub = `${esc(STAGES[numSt] || '')} · ${pct(sel, base)}`;
        }

        rows += `
            <div class="sp-funnel-foot">
                <div><div class="k">Selected stage</div><div class="v">${n(sel)}</div><div class="s">${selSub}</div></div>
                <div><div class="k">Lead to booking</div><div class="v">${pct(m.stage_5, base, 2)}</div><div class="s">${n(m.bookings || m.stage_5)} units / ${n(m.stage_5)} clients</div></div>
                <div><div class="k">Visit to booking</div><div class="v">${pct(m.stage_5, m.stage_4)}</div><div class="s">${n(m.bookings || m.stage_5)} units / ${n(m.stage_4)} visits</div></div>
            </div>`;
        el.innerHTML = rows;
        $$('.sp-funnel-row', el).forEach((r) => {
            if (r.dataset.stage) {
                r.addEventListener('click', (ev) => {
                    const st = r.dataset.stage;
                    if (st) {
                        funnelState[el.id] = funnelState[el.id] || {};
                        funnelState[el.id].selected = st;
                        const foot = el.querySelector('.sp-funnel-foot');
                        if (foot) {
                            let sv = 0;
                            let sub = '';
                            if (st === 'no_response') {
                                sv = m.no_response || 0;
                                sub = `No Response · ${pct(sv, base)}`;
                            } else if (st === 'uncontacted') {
                                sv = m.uncontacted !== undefined ? m.uncontacted : Math.max(0, base - (m.stage_2 || 0));
                                sub = `Not Contacted · ${pct(sv, base)}`;
                            } else if (String(st) === '5') {
                                sv = m.bookings || m.stage_5 || 0;
                                sub = `Booked · ${n(sv)} units sold by ${n(m.stage_5 || 0)} clients`;
                            } else {
                                const numSt = Number(st);
                                sv = m['stage_' + numSt] || 0;
                                sub = `${esc(STAGES[numSt] || '')} · ${pct(sv, base)}`;
                            }
                            foot.children[0].querySelector('.v').textContent = n(sv);
                            foot.children[0].querySelector('.s').textContent = sub;
                        }
                        $$('.sp-funnel-row', el).forEach(row => row.classList.toggle('sel', row.dataset.stage == st));
                    }
                });
            }
        });
        bindGapClicks(el);
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

    // ---------- drawer (attached to document.body to guarantee viewport-fixed display) ----------
    function ensureDrawerInBody() {
        let d = document.getElementById('spDrawer');
        let b = document.getElementById('spDrawerBg');
        if (d && d.parentElement !== document.body) {
            document.body.appendChild(d);
        }
        if (b && b.parentElement !== document.body) {
            document.body.appendChild(b);
        }
        return { drawer: d, bg: b };
    }

    function openDrawer(title, sub, html) {
        const { drawer, bg } = ensureDrawerInBody();
        if (!drawer || !bg) return;
        const titleEl = document.getElementById('spDrawerTitle');
        const subEl = document.getElementById('spDrawerSub');
        const bodyEl = document.getElementById('spDrawerBody');
        if (titleEl) titleEl.textContent = title;
        if (subEl) subEl.innerHTML = sub || '';
        if (bodyEl) bodyEl.innerHTML = html;

        bg.style.display = 'block';
        drawer.style.display = 'flex';
        void drawer.offsetWidth; // Force layout recalculation
        drawer.classList.add('open');
        bg.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        const { drawer, bg } = ensureDrawerInBody();
        if (drawer) {
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
        }
        if (bg) {
            bg.classList.remove('open');
            setTimeout(() => {
                if (bg && !bg.classList.contains('open')) {
                    bg.style.display = 'none';
                }
            }, 260);
        }
        document.body.style.overflow = '';
        const searchWrap = document.getElementById('spDrawerSearchWrap');
        if (searchWrap) searchWrap.style.display = 'none';
        const expBtn = document.getElementById('spDrawerExportBtn');
        if (expBtn) expBtn.style.display = 'none';
    }

    // ---------- data drill-down (cards, funnel stages, attention items, queues) ----------
    async function handleDrillDownClick(btn, ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        if (btn.dataset.stage) {
            const st = btn.dataset.stage;
            const funnelEl = btn.closest('#spFunnelOverview, #spFunnelTeam, #spFunnelExec') || btn.parentElement;
            if (funnelEl) {
                funnelState[funnelEl.id] = funnelState[funnelEl.id] || {};
                funnelState[funnelEl.id].selected = st;
                $$('.sp-funnel-row', funnelEl).forEach(row => row.classList.toggle('sel', String(row.dataset.stage) === String(st)));
                const foot = funnelEl.querySelector('.sp-funnel-foot');
                if (foot && funnelState[funnelEl.id] && funnelState[funnelEl.id].m) {
                    const m = funnelState[funnelEl.id].m;
                    const base = m.stage_1 || 0;
                    let sv = 0;
                    let sub = '';
                    if (st === 'no_response') {
                        sv = m.no_response || 0;
                        sub = `No Response · ${pct(sv, base)}`;
                    } else if (st === 'uncontacted') {
                        sv = m.uncontacted !== undefined ? m.uncontacted : Math.max(0, base - (m.stage_2 || 0));
                        sub = `Not Contacted · ${pct(sv, base)}`;
                    } else if (String(st) === '5') {
                        sv = m.bookings || m.stage_5 || 0;
                        sub = `Booked · ${n(sv)} units sold by ${n(m.stage_5 || 0)} clients`;
                    } else {
                        const numSt = Number(st);
                        sv = m['stage_' + numSt] || 0;
                        sub = `${esc(STAGES[numSt] || '')} · ${pct(sv, base)}`;
                    }
                    if (foot && foot.children[0]) {
                        foot.children[0].querySelector('.v').textContent = n(sv);
                        foot.children[0].querySelector('.s').textContent = sub;
                    }
                }
            }
        }
        const key = btn.dataset.gap;
        if (!key) return;

        // Build active filter query parameters
        const params = new URLSearchParams();
        params.set('key', key);

        const cycleInput = document.getElementById('spCycle');
        const fromInput  = document.getElementById('spFrom');
        const toInput    = document.getElementById('spTo');
        const presetInput= document.getElementById('spPreset');
        const projInput  = document.getElementById('spProject');
        const teamInput  = document.getElementById('spTeam');
        const execInput  = document.getElementById('spExec');

        let cycleVal = (cycleInput && cycleInput.value !== undefined) ? cycleInput.value : (D.filters && D.filters.cycle || '');
        const fromVal  = (fromInput && fromInput.value) ? fromInput.value : (D.filters && D.filters.from || '');
        const toVal    = (toInput && toInput.value) ? toInput.value : (D.filters && D.filters.to || '');
        let presetVal= (presetInput && presetInput.value) ? presetInput.value : (D.filters && D.filters.preset || '');

        if (fromVal && toVal) {
            if (presetVal === 'custom' || cycleVal === 'custom' || (!presetVal && (!cycleVal || cycleVal.startsWith('preset:')))) {
                cycleVal = 'custom';
                presetVal = 'custom';
            }
        }
        const projVal  = (projInput && projInput.value) ? projInput.value : (D.filters && D.filters.project_id || '');
        const teamVal  = (teamInput && teamInput.value) ? teamInput.value : (D.filters && D.filters.team_id || '');
        const execVal  = btn.dataset.execId || ((execInput && execInput.value) ? execInput.value : (D.filters && D.filters.executive_id || ''));
        const teamKeyVal = btn.dataset.teamKey || '';

        if (cycleVal) params.set('cycle', cycleVal);
        if (fromVal) params.set('from', fromVal);
        if (toVal) params.set('to', toVal);
        if (presetVal) params.set('preset', presetVal);
        if (projVal) params.set('project_id', projVal);
        if (teamVal) params.set('team_id', teamVal);
        if (execVal) params.set('executive_id', execVal);
        if (teamKeyVal) params.set('team_key', teamKeyVal);

        // Hide search/export while loading
        const searchWrap = document.getElementById('spDrawerSearchWrap');
        if (searchWrap) searchWrap.style.display = 'none';
        const expBtn = document.getElementById('spDrawerExportBtn');
        if (expBtn) expBtn.style.display = 'none';

        openDrawer('Loading…', '', '<div class="sp-empty"><i class="fa-regular fa-spinner fa-spin" style="font-size:26px;color:var(--sp-green);margin-bottom:12px;display:block"></i> Loading records…</div>');

        try {
            const baseUrl = (window.base_url || (window.SP_DATA && window.SP_DATA.baseUrl) || '/TPHL-ERP').replace(/\/+$/, '');
            const res = await fetch(`${baseUrl}/sales-performance/gap-details?${params.toString()}`, { credentials: 'same-origin' });
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            const j = await res.json();
            const rows = j.rows || [];
            let html = '';

            // Dedicated visual accordion breakdown for Today's Follow-ups
            if ((key === 'today_followups' || key === 'due_today') && j.today_summary) {
                const ts = j.today_summary;
                const execs = ts.by_executive || [];

                // Top Overview Card
                html += `
                    <div class="sp-today-overview-card" style="background:var(--sp-card);border:1px solid var(--sp-border);border-radius:10px;padding:14px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                        <div style="font-size:12.5px;font-weight:700;color:var(--sp-text);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
                            <span style="display:inline-flex;align-items:center;gap:6px"><i class="fa-solid fa-calendar-check" style="color:var(--sp-green)"></i> Today's Follow-up Overview</span>
                            <span style="font-size:11.5px;color:var(--sp-muted)">Total Scheduled: <b style="color:var(--sp-text)">${n(ts.total)}</b> across ${execs.length} Executives</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                            <div class="sp-today-type-btn" data-status="completed" style="background:#ecfdf5;border:1.5px solid #10b981;border-radius:8px;padding:9px 12px;cursor:pointer;transition:all .15s" title="Click to filter Completed Follow-ups">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <span style="font-size:11.5px;font-weight:700;color:#065f46;display:inline-flex;align-items:center;gap:5px"><i class="fa-solid fa-circle-check"></i> Completed Today</span>
                                    <span style="font-size:10px;font-weight:700;background:#d1fae5;color:#047857;padding:1px 6px;border-radius:99px">${pct(ts.completed, ts.total)}</span>
                                </div>
                                <div style="font-size:18px;font-weight:800;color:#047857;margin:3px 0 1px">${n(ts.completed)}</div>
                                <div style="font-size:10.5px;color:#059669">Follow-ups logged today</div>
                            </div>
                            <div class="sp-today-type-btn" data-status="incomplete" style="background:#fffbeb;border:1.5px solid #f59e0b;border-radius:8px;padding:9px 12px;cursor:pointer;transition:all .15s" title="Click to filter Incomplete / Pending Follow-ups">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <span style="font-size:11.5px;font-weight:700;color:#92400e;display:inline-flex;align-items:center;gap:5px"><i class="fa-solid fa-clock"></i> Incomplete / Pending</span>
                                    <span style="font-size:10px;font-weight:700;background:#fef3c7;color:#b45309;padding:1px 6px;border-radius:99px">${pct(ts.incomplete, ts.total)}</span>
                                </div>
                                <div style="font-size:18px;font-weight:800;color:#b45309;margin:3px 0 1px">${n(ts.incomplete)}</div>
                                <div style="font-size:10.5px;color:#d97706">Awaiting callback today</div>
                            </div>
                        </div>

                        <div style="border-top:1px dashed var(--sp-border);padding-top:10px;margin-top:6px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
                            <div style="display:flex;align-items:center;gap:6px">
                                <span style="font-size:11px;color:var(--sp-muted);font-weight:600">Filter:</span>
                                <button type="button" class="sp-acc-tab-btn active" data-status="all">All (${n(ts.total)})</button>
                                <button type="button" class="sp-acc-tab-btn" data-status="incomplete" style="border-color:#fde68a;background:#fffbeb;color:#b45309">Incomplete (${n(ts.incomplete)})</button>
                                <button type="button" class="sp-acc-tab-btn" data-status="completed" style="border-color:#a7f3d0;background:#ecfdf5;color:#047857">Completed (${n(ts.completed)})</button>
                            </div>
                            <div style="display:flex;align-items:center;gap:6px">
                                <button type="button" class="sp-btn sm" id="spAccExpandAllBtn" style="font-size:10.5px;padding:2px 8px;height:24px"><i class="fa-regular fa-arrows-maximize"></i> Expand All</button>
                                <button type="button" class="sp-btn sm" id="spAccCollapseAllBtn" style="font-size:10.5px;padding:2px 8px;height:24px"><i class="fa-regular fa-arrows-minimize" style="transform:rotate(180deg)"></i> Collapse All</button>
                            </div>
                        </div>
                    </div>`;

                if (!rows.length) {
                    html += '<div class="sp-empty"><i class="fa-regular fa-inbox" style="font-size:28px;margin-bottom:10px;opacity:0.4;display:block"></i>No follow-up records found for today.</div>';
                } else {
                    html += `<div style="font-size:11.5px;color:var(--sp-soft);margin-bottom:8px" id="spDrawerFilteredNote"></div>`;
                    html += `<div class="sp-scroll" style="max-height:calc(100vh - 275px);overflow-y:auto;padding-right:4px">`;

                    // Group rows by owner_id
                    const leadsByOwner = {};
                    rows.forEach(r => {
                        const oid = r.owner_id || 0;
                        if (!leadsByOwner[oid]) leadsByOwner[oid] = [];
                        leadsByOwner[oid].push(r);
                    });

                    const getInitials = (nm) => {
                        if (!nm || nm === '—' || nm === 'Not Assigned') return '?';
                        const parts = nm.trim().split(/\s+/);
                        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
                        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
                    };

                    execs.forEach((e, idx) => {
                        const execLeads = leadsByOwner[e.owner_id] || [];
                        const isExpanded = (idx === 0);
                        html += `
                            <div class="sp-exec-acc-card" data-owner-id="${e.owner_id}" data-total="${e.total}" data-completed="${e.completed}" data-incomplete="${e.incomplete}" style="margin-bottom:10px;flex-shrink:0">
                                <div class="sp-exec-acc-header">
                                    <div style="display:flex;align-items:center;gap:11px;min-width:0">
                                        <div style="width:34px;height:34px;border-radius:50%;background:#e0e7ff;color:#3730a3;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0">
                                            ${getInitials(e.owner_name)}
                                        </div>
                                        <div style="min-width:0">
                                            <div style="font-weight:700;font-size:13px;color:var(--sp-text);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                ${esc(e.owner_name)}
                                            </div>
                                            <div style="font-size:11px;color:var(--sp-muted);line-height:1.2;margin-top:2px;display:flex;align-items:center;gap:4px">
                                                <i class="fa-regular fa-users" style="font-size:9.5px;opacity:0.7"></i>
                                                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(e.team_name)}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;margin-left:12px">
                                        <span class="sp-tag" style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:99px;background:#f1f5f9;color:#334155" title="Scheduled Today">${n(e.total)} Scheduled</span>
                                        <span class="sp-tag" style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;background:#d1fae5;color:#047857" title="Done Today"><i class="fa-solid fa-circle-check" style="font-size:9.5px;margin-right:2px"></i>${n(e.completed)} Done</span>
                                        <span class="sp-tag" style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;background:#fef3c7;color:#b45309" title="Incomplete Today"><i class="fa-regular fa-clock" style="font-size:9.5px;margin-right:2px"></i>${n(e.incomplete)} Incomplete</span>
                                        <i class="fa-solid fa-chevron-down sp-acc-chevron" style="font-size:10.5px;color:var(--sp-muted);margin-left:4px;transition:transform .2s;${isExpanded ? 'transform:rotate(180deg)' : ''}"></i>
                                    </div>
                                </div>
                                <div class="sp-exec-acc-body" style="display:${isExpanded ? 'block' : 'none'};border-top:1px solid var(--sp-border)">
                                    <table class="sp-dtable" style="width:100%;margin:0;font-size:11.5px">
                                        <thead>
                                            <tr style="background:#fff">
                                                <th style="min-width:170px">Lead / Record</th>
                                                <th style="min-width:200px">Action & Details</th>
                                                <th style="width:90px;text-align:right;white-space:nowrap">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${execLeads.map(r => {
                                                const link = r.lead_id ? `<a href="${baseUrl}/view-lead/${r.lead_id}" target="_blank" style="color:var(--sp-green);font-weight:600;text-decoration:none" title="Open lead #${r.lead_id} in new tab">${esc(r.lead_name)} <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:3px"></i></a>` : `<b style="color:var(--sp-text)">${esc(r.lead_name)}</b>`;
                                                const isCompleted = (r.status_badge === 'Completed' || r.is_completed == 1 || r.is_completed === '1' || r.is_completed === true);
                                                let badge = '';
                                                if (isCompleted) {
                                                    badge = '<span class="sp-tag" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-solid fa-circle-check" style="font-size:9px;margin-right:2px"></i>Completed</span>';
                                                } else {
                                                    badge = '<span class="sp-tag" style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-regular fa-clock" style="font-size:9px;margin-right:2px"></i>Incomplete</span>';
                                                }
                                                const statusAttr = isCompleted ? 'completed' : 'incomplete';
                                                return `
                                                    <tr data-status="${statusAttr}" data-lead-id="${r.lead_id || ''}">
                                                        <td>
                                                            <div style="font-size:12.5px">${link}</div>
                                                            ${r.phone ? `<div style="color:var(--sp-soft);font-size:11px;margin-top:2px;font-family:monospace">${esc(r.phone)}</div>` : ''}
                                                        </td>
                                                        <td>
                                                            <div style="font-size:11.5px;line-height:1.4">${esc(r.info || '')} ${badge}</div>
                                                        </td>
                                                        <td style="white-space:nowrap;font-size:11px;color:var(--sp-muted);text-align:right">
                                                            ${esc((r.event_date || '').substring(0, 10))}
                                                        </td>
                                                    </tr>
                                                `;
                                            }).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                    if (j.total > rows.length) {
                        html += `<div class="sp-note" style="margin-top:10px;font-size:11.5px"><i class="fa-regular fa-info-circle"></i> Showing latest ${rows.length} of ${n(j.total)} records. Click <b>Export CSV</b> to download all.</div>`;
                    }
                }
            } else {
                if (key === 'collection' && j.collection_summary) {
                    const cs = j.collection_summary;
                    html += `
                        <div class="sp-col-breakdown-card" style="background:var(--sp-card);border:1px solid var(--sp-border);border-radius:10px;padding:12px 14px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
                            <div style="font-size:12px;font-weight:700;color:var(--sp-text);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
                                <span style="display:inline-flex;align-items:center;gap:6px"><i class="fa-solid fa-sack-dollar" style="color:var(--sp-green)"></i> Collection Source Breakdown:</span>
                                <span style="font-size:11px;color:var(--sp-muted)">Total: <b style="color:var(--sp-text)">${tk(cs.total_amt)}</b></span>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                <div class="sp-col-type-btn" data-type="new" style="background:#ecfdf5;border:1.5px solid #10b981;border-radius:8px;padding:9px 12px;cursor:pointer;transition:all .15s" title="Click to view payments from New Bookings">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <span style="font-size:11.5px;font-weight:700;color:#065f46;display:inline-flex;align-items:center;gap:5px"><i class="fa-solid fa-sparkles"></i> New Bookings</span>
                                        <span style="font-size:10px;font-weight:700;background:#d1fae5;color:#047857;padding:1px 6px;border-radius:99px">${pct(cs.new_booking_amt, cs.total_amt)}</span>
                                    </div>
                                    <div style="font-size:16px;font-weight:800;color:#047857;margin:3px 0 1px">${tk(cs.new_booking_amt)}</div>
                                    <div style="font-size:10.5px;color:#059669">${n(cs.new_booking_count)} payments booked in period</div>
                                </div>
                                <div class="sp-col-type-btn" data-type="prev" style="background:#eff6ff;border:1.5px solid #3b82f6;border-radius:8px;padding:9px 12px;cursor:pointer;transition:all .15s" title="Click to view payments from Previous Bookings">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <span style="font-size:11.5px;font-weight:700;color:#1e40af;display:inline-flex;align-items:center;gap:5px"><i class="fa-regular fa-clock-rotate-left"></i> Previous Bookings</span>
                                        <span style="font-size:10px;font-weight:700;background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:99px">${pct(cs.prev_booking_amt, cs.total_amt)}</span>
                                    </div>
                                    <div style="font-size:16px;font-weight:800;color:#1d4ed8;margin:3px 0 1px">${tk(cs.prev_booking_amt)}</div>
                                    <div style="font-size:10.5px;color:#2563eb">${n(cs.prev_booking_count)} installment / past payments</div>
                                </div>
                            </div>
                            <div style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--sp-border);display:flex;align-items:center;justify-content:space-between;font-size:11px">
                                <span style="color:var(--sp-muted)">Click a box above to filter, or:</span>
                                <button type="button" class="sp-btn sm" id="spColShowAllBtn" style="font-size:10.5px;padding:2px 8px;height:24px"><i class="fa-regular fa-list"></i> Show All (${n(rows.length)})</button>
                            </div>
                        </div>`;
                } else {
                    html += `<p style="font-size:12.5px;color:var(--sp-muted);margin:0 0 12px;line-height:1.5">${esc(j.description || '')}</p>`;
                }

                if (!rows.length) {
                    html += '<div class="sp-empty"><i class="fa-regular fa-inbox" style="font-size:28px;margin-bottom:10px;opacity:0.4;display:block"></i>No records found for this selection.</div>';
                } else {
                    html += `
                        <div style="font-size:11.5px;color:var(--sp-soft);margin-bottom:8px" id="spDrawerFilteredNote"></div>
                        <div class="sp-scroll" style="max-height:calc(100vh - 215px);overflow-y:auto;border:1px solid var(--sp-border);border-radius:8px">
                            <table class="sp-dtable" style="width:100%;margin:0">
                                <thead>
                                    <tr>
                                        <th style="min-width:180px">Lead / Record</th>
                                        <th style="min-width:140px">Owner · Team</th>
                                        <th style="min-width:130px">Detail</th>
                                        <th style="min-width:90px;white-space:nowrap">Date</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    rows.forEach((r) => {
                        const link = r.lead_id ? `<a href="${baseUrl}/view-lead/${r.lead_id}" target="_blank" style="color:var(--sp-green);font-weight:600;text-decoration:none" title="Open lead #${r.lead_id} in new tab">${esc(r.lead_name)} <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:3px"></i></a>` : `<b style="color:var(--sp-text)">${esc(r.lead_name)}</b>`;
                        let badge = '';
                        if (r.status_badge === 'Completed') {
                            badge = '<span class="sp-tag" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-solid fa-circle-check" style="font-size:9px;margin-right:2px"></i>Completed</span>';
                        } else if (r.status_badge === 'Incomplete') {
                            badge = '<span class="sp-tag" style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-regular fa-clock" style="font-size:9px;margin-right:2px"></i>Incomplete</span>';
                        } else if (r.status_badge === 'New Booking') {
                            badge = '<span class="sp-tag" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-weight:700;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-solid fa-sparkles" style="font-size:9px;margin-right:2px"></i>New Booking</span>';
                        } else if (r.status_badge === 'Previous Booking') {
                            badge = '<span class="sp-tag" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-weight:600;font-size:10px;padding:2px 6px;border-radius:4px"><i class="fa-regular fa-clock-rotate-left" style="font-size:9px;margin-right:2px"></i>Previous Booking</span>';
                        } else if (r.status_badge) {
                            badge = `<span class="sp-tag" style="margin-left:5px;font-size:10px;vertical-align:middle">${esc(r.status_badge)}</span>`;
                        }
                        const btypeAttr = (r.is_new_booking !== undefined) ? `data-btype="${r.is_new_booking ? 'new' : 'prev'}"` : '';
                        const statusAttr = r.status_badge ? `data-status="${esc(r.status_badge.toLowerCase())}"` : '';
                        const ownerAttr = r.owner_id ? `data-owner-id="${esc(r.owner_id)}"` : '';
                        html += `<tr ${btypeAttr} ${statusAttr} ${ownerAttr}>
                            <td>
                                <div style="font-size:12.5px">${link}</div>
                                ${r.phone ? `<div style="color:var(--sp-soft);font-size:11px;margin-top:2px;font-family:monospace">${esc(r.phone)}</div>` : ''}
                            </td>
                            <td>
                                ${(!r.owner_name || r.owner_name === '—' || r.owner_name === 'Not Assigned')
                                    ? '<span class="sp-tag" style="background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.3);font-size:11px;padding:2px 7px;border-radius:4px;font-weight:600;display:inline-block"><i class="fa-regular fa-user-slash" style="margin-right:3px"></i>Not Assigned</span>'
                                    : `<div style="font-weight:600;font-size:12px">${esc(r.owner_name)}</div>`}
                                ${(r.team_name && r.team_name !== '—')
                                    ? `<div style="color:var(--sp-soft);font-size:11px;margin-top:2px"><i class="fa-regular fa-users" style="font-size:9.5px;margin-right:3px;opacity:0.7"></i>${esc(r.team_name)}</div>`
                                    : '<div style="color:var(--sp-muted);font-size:11px;margin-top:2px;opacity:0.6">—</div>'}
                            </td>
                            <td>
                                <div style="font-size:11.5px">${esc(r.info || '')} ${badge}</div>
                            </td>
                            <td style="white-space:nowrap;font-size:11px;color:var(--sp-muted)">
                                ${esc((r.event_date || '').substring(0, 10))}
                            </td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>`;
                    if (j.total > rows.length) {
                        html += `<div class="sp-note" style="margin-top:10px;font-size:11.5px"><i class="fa-regular fa-info-circle"></i> Showing latest ${rows.length} of ${n(j.total)} records. Click <b>Export CSV</b> to download all.</div>`;
                    }
                }
            }

            openDrawer(j.label || 'Records', `${n(j.total || 0)} records`, html);

            // Wire up interactive today's followups accordion interactions
            if ((key === 'today_followups' || key === 'due_today') && j.today_summary) {
                // Header click toggles accordion body
                document.querySelectorAll('.sp-exec-acc-header').forEach(hdr => {
                    hdr.addEventListener('click', () => {
                        const card = hdr.closest('.sp-exec-acc-card');
                        const body = card.querySelector('.sp-exec-acc-body');
                        const ch = hdr.querySelector('.sp-acc-chevron');
                        const isHidden = (body.style.display === 'none' || !body.style.display);
                        body.style.display = isHidden ? 'block' : 'none';
                        if (ch) ch.style.transform = isHidden ? 'rotate(180deg)' : '';
                    });
                });

                // Expand All / Collapse All
                const expAllBtn = document.getElementById('spAccExpandAllBtn');
                const colAllBtn = document.getElementById('spAccCollapseAllBtn');
                if (expAllBtn) {
                    expAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.sp-exec-acc-card').forEach(c => {
                            const b = c.querySelector('.sp-exec-acc-body');
                            const ch = c.querySelector('.sp-acc-chevron');
                            if (b) b.style.display = 'block';
                            if (ch) ch.style.transform = 'rotate(180deg)';
                        });
                    });
                }
                if (colAllBtn) {
                    colAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.sp-exec-acc-card').forEach(c => {
                            const b = c.querySelector('.sp-exec-acc-body');
                            const ch = c.querySelector('.sp-acc-chevron');
                            if (b) b.style.display = 'none';
                            if (ch) ch.style.transform = '';
                        });
                    });
                }

                // Filter by Status (tabs & cards)
                let currentStatusFilter = 'all';
                const applyStatusFilter = (st) => {
                    currentStatusFilter = st;
                    document.querySelectorAll('.sp-acc-tab-btn').forEach(btn => {
                        const active = (btn.dataset.status === st);
                        btn.classList.toggle('active', active);
                    });
                    document.querySelectorAll('.sp-today-type-btn').forEach(btn => {
                        const active = (btn.dataset.status === st);
                        btn.style.outline = active ? '2px solid var(--sp-green)' : 'none';
                        btn.style.opacity = (st === 'all' || active) ? '1' : '0.55';
                    });

                    let totalVisibleLeads = 0;
                    let visibleExecs = 0;
                    document.querySelectorAll('.sp-exec-acc-card').forEach(card => {
                        const trs = card.querySelectorAll('tbody tr');
                        let cardMatches = 0;
                        trs.forEach(tr => {
                            const trSt = tr.dataset.status;
                            const match = (st === 'all' || trSt === st);
                            tr.style.display = match ? '' : 'none';
                            if (match) cardMatches++;
                        });

                        if (cardMatches > 0) {
                            card.style.display = '';
                            totalVisibleLeads += cardMatches;
                            visibleExecs++;
                            if (st !== 'all') {
                                const b = card.querySelector('.sp-exec-acc-body');
                                const ch = card.querySelector('.sp-acc-chevron');
                                if (b) b.style.display = 'block';
                                if (ch) ch.style.transform = 'rotate(180deg)';
                            }
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    const note = document.getElementById('spDrawerFilteredNote');
                    if (note) {
                        if (st === 'all') {
                            note.textContent = '';
                        } else if (totalVisibleLeads > 0) {
                            note.textContent = `Showing ${totalVisibleLeads} ${st} follow-ups across ${visibleExecs} executives`;
                        } else {
                            note.textContent = `No ${st} follow-ups found for today`;
                        }
                    }

                    // Empty state element inside scroll container if 0 results
                    let emptyEl = document.getElementById('spAccStatusEmpty');
                    const scrollContainer = document.querySelector('#spDrawerBody .sp-scroll');
                    if (totalVisibleLeads === 0) {
                        if (!emptyEl && scrollContainer) {
                            emptyEl = document.createElement('div');
                            emptyEl.id = 'spAccStatusEmpty';
                            emptyEl.className = 'sp-empty';
                            emptyEl.style.padding = '36px 12px';
                            scrollContainer.appendChild(emptyEl);
                        }
                        if (emptyEl) {
                            const iconClass = (st === 'completed') ? 'circle-check' : 'clock';
                            const iconColor = (st === 'completed') ? '#10b981' : '#f59e0b';
                            emptyEl.innerHTML = `<i class="fa-regular fa-${iconClass}" style="font-size:32px;color:${iconColor};opacity:0.8;margin-bottom:10px;display:block"></i><b style="font-size:13px;color:var(--sp-text)">No ${st} follow-ups</b><p style="margin:4px 0 0;font-size:11.5px;color:var(--sp-muted)">There are no ${st} follow-ups logged for today yet.</p>`;
                            emptyEl.style.display = 'block';
                        }
                    } else if (emptyEl) {
                        emptyEl.style.display = 'none';
                    }
                };

                document.querySelectorAll('.sp-acc-tab-btn').forEach(btn => {
                    btn.addEventListener('click', () => applyStatusFilter(btn.dataset.status));
                });
                document.querySelectorAll('.sp-today-type-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const target = (currentStatusFilter === btn.dataset.status) ? 'all' : btn.dataset.status;
                        applyStatusFilter(target);
                    });
                });

                // Search input for accordion
                if (searchWrap && rows.length > 2) {
                    searchWrap.style.display = 'block';
                    const searchInput = document.getElementById('spDrawerFilter');
                    if (searchInput) {
                        searchInput.value = '';
                        setTimeout(() => searchInput.focus(), 80);
                        searchInput.oninput = () => {
                            const q = searchInput.value.toLowerCase().trim();
                            let totalMatched = 0;
                            document.querySelectorAll('.sp-exec-acc-card').forEach(card => {
                                const headerText = card.querySelector('.sp-exec-acc-header').textContent.toLowerCase();
                                const trs = Array.from(card.querySelectorAll('tbody tr'));
                                let cardMatches = 0;

                                trs.forEach(tr => {
                                    const trSt = tr.dataset.status;
                                    const statusMatch = (currentStatusFilter === 'all' || trSt === currentStatusFilter);
                                    const textMatch = !q || headerText.includes(q) || tr.textContent.toLowerCase().includes(q);
                                    const match = statusMatch && textMatch;
                                    tr.style.display = match ? '' : 'none';
                                    if (match) cardMatches++;
                                });

                                if (cardMatches > 0) {
                                    card.style.display = '';
                                    totalMatched += cardMatches;
                                    if (q) {
                                        const b = card.querySelector('.sp-exec-acc-body');
                                        const ch = card.querySelector('.sp-acc-chevron');
                                        if (b) b.style.display = 'block';
                                        if (ch) ch.style.transform = 'rotate(180deg)';
                                    }
                                } else {
                                    card.style.display = 'none';
                                }
                            });

                            const note = document.getElementById('spDrawerFilteredNote');
                            if (note) {
                                note.textContent = q ? `Showing ${totalMatched} matching follow-ups (filtered from ${rows.length})` : '';
                            }
                            let emptyEl = document.getElementById('spAccStatusEmpty');
                            if (q && totalMatched === 0) {
                                const scrollContainer = document.querySelector('#spDrawerBody .sp-scroll');
                                if (!emptyEl && scrollContainer) {
                                    emptyEl = document.createElement('div');
                                    emptyEl.id = 'spAccStatusEmpty';
                                    emptyEl.className = 'sp-empty';
                                    emptyEl.style.padding = '36px 12px';
                                    scrollContainer.appendChild(emptyEl);
                                }
                                if (emptyEl) {
                                    emptyEl.innerHTML = `<i class="fa-regular fa-magnifying-glass" style="font-size:28px;opacity:0.4;margin-bottom:8px;display:block"></i>No follow-ups match "${esc(q)}".`;
                                    emptyEl.style.display = 'block';
                                }
                            } else if (emptyEl && !q && currentStatusFilter === 'all') {
                                emptyEl.style.display = 'none';
                            }
                        };
                    }
                }
            } else {
                // Wire up interactive collection breakdown filters
                if (key === 'collection' && j.collection_summary) {
                    const colBtns = document.querySelectorAll('.sp-col-type-btn');
                    const showAllBtn = document.getElementById('spColShowAllBtn');
                    const filterCollectionType = (type) => {
                        colBtns.forEach(btn => {
                            const isActive = (btn.dataset.type === type);
                            btn.style.opacity = (type === 'all' || isActive) ? '1' : '0.45';
                            btn.style.outline = isActive ? '2px solid var(--sp-green)' : 'none';
                        });
                        const trs = Array.from(document.querySelectorAll('#spDrawerBody table tbody tr'));
                        let matched = 0;
                        trs.forEach(tr => {
                            const btype = tr.dataset.btype;
                            const match = (type === 'all' || btype === type);
                            tr.style.display = match ? '' : 'none';
                            if (match) matched++;
                        });
                        const note = document.getElementById('spDrawerFilteredNote');
                        if (note) {
                            note.textContent = type === 'all' ? '' : `Showing ${matched} ${type === 'new' ? 'New Booking' : 'Previous Booking'} payments (filtered from ${rows.length})`;
                        }
                    };
                    colBtns.forEach(btn => btn.addEventListener('click', () => filterCollectionType(btn.dataset.type)));
                    if (showAllBtn) showAllBtn.addEventListener('click', () => filterCollectionType('all'));
                }

                // Wire up real-time search box
                if (searchWrap && rows.length > 2) {
                    searchWrap.style.display = 'block';
                    const searchInput = document.getElementById('spDrawerFilter');
                    if (searchInput) {
                        searchInput.value = '';
                        setTimeout(() => searchInput.focus(), 80);
                        searchInput.oninput = () => {
                            const q = searchInput.value.toLowerCase().trim();
                            const trs = Array.from(document.querySelectorAll('#spDrawerBody table tbody tr'));
                            let matched = 0;
                            trs.forEach(tr => {
                                const match = !q || tr.textContent.toLowerCase().includes(q);
                                tr.style.display = match ? '' : 'none';
                                if (match) matched++;
                            });
                            const note = document.getElementById('spDrawerFilteredNote');
                            if (note) {
                                note.textContent = q ? `Showing ${matched} matching rows (filtered from ${rows.length})` : '';
                            }
                        };
                    }
                }
            }

            // Wire up Export CSV button
            if (expBtn && rows.length > 0) {
                expBtn.style.display = 'inline-flex';
                expBtn.onclick = (e) => {
                    e.preventDefault();
                    const expParams = new URLSearchParams(params);
                    expParams.set('export', 'csv');
                    window.location.href = `${baseUrl}/sales-performance/gap-details?${expParams.toString()}`;
                };
            }
        } catch (e) {
            console.error('Drill-down load failed:', e);
            openDrawer('Could not load', '', `<div class="sp-empty"><i class="fa-regular fa-triangle-exclamation" style="font-size:24px;color:var(--sp-amber);margin-bottom:8px;display:block"></i>Failed to load records (${esc(e.message || 'Please retry')}).</div>`);
        }
    }

    function bindGapClicks(root = document) {
        if (!root) return;
        root.querySelectorAll('[data-gap]').forEach((el) => {
            el.style.cursor = 'pointer';
            if (!el._hasGapBound) {
                el._hasGapBound = true;
                el.addEventListener('click', (e) => {
                    if (e.target.closest('#spDrawer a')) return;
                    handleDrillDownClick(el, e);
                });
            }
        });
    }

    // Ensure drawer is placed in document.body
    ensureDrawerInBody();

    // Capture-phase global click delegation
    document.addEventListener('click', (ev) => {
        if (ev.target.closest('#spDrawer a')) return;
        if (ev.target.closest('#spDrawerClose') || ev.target.closest('#spDrawerBg')) {
            ev.preventDefault();
            closeDrawer();
            return;
        }
        const btn = ev.target.closest('[data-gap]');
        if (!btn) return;
        if (btn.closest('#spDrawer') && !btn.classList.contains('sp-mini')) return;
        handleDrillDownClick(btn, ev);
    }, true);

    document.addEventListener('keydown', (e) => e.key === 'Escape' && closeDrawer());

    // Bind current DOM elements
    bindGapClicks(document);

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
                <td class="num"><span data-gap="stage_1" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view leads received for ${esc(t.name)}">${n(t.stage_1)}</span></td>
                <td class="num"><span data-gap="stage_2" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view contacted leads for ${esc(t.name)}">${t.contact_rate}%</span></td>
                <td class="num sp-col-no-response"><span data-gap="no_response_leads" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view No Response leads for ${esc(t.name)}">${n(t.no_response || 0)}</span> <small>(${t.no_response_rate || 0}%)</small></td>
                <td class="num">${hrs(t.median_response_hours)}</td>
                <td class="num"><span data-gap="follow_ups" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view follow-ups for ${esc(t.name)}">${n(t.follow_ups)}</span></td>
                <td class="num"><span data-gap="visits_done" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view visits done for ${esc(t.name)}">${n(t.visits_done)}</span></td>
                <td class="num"><span data-gap="bookings" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer;font-weight:700;color:var(--sp-green)" title="Click to view bookings for ${esc(t.name)}">${n(t.bookings)}</span></td>
                <td class="num"><span data-gap="confirmed_sales" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view confirmed sales for ${esc(t.name)}">${n(t.confirmed_sales)}</span> / ${t.target_bookings ? n(t.target_bookings) : '—'}</td>
                <td>${achCell(t)}</td>
                <td class="num"><span data-gap="collection" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view collection for ${esc(t.name)}">${tk(t.collection)}</span></td>
                <td class="num" style="${t.overdue > 0 ? 'color:var(--sp-amber);font-weight:600' : ''}"><span data-gap="overdue" data-team-key="${esc(t.key)}" class="click" style="cursor:pointer" title="Click to view overdue for ${esc(t.name)}">${n(t.overdue)}</span></td>
                <td><i class="fa-regular fa-arrow-right" style="color:var(--sp-green)"></i></td>
            </tr>`;

        function drawTeams() {
            const sorted = [...real].sort((a, b) => (b[sortKey] || 0) - (a[sortKey] || 0));
            $('tbody', teamTable).innerHTML = sorted.map((t, i) => row(t, i, false)).join('') + other.map((t) => row(t, 0, true)).join('');
            const o = D.overall;
            $('tfoot', teamTable).innerHTML = `<tr><td></td><td>Total</td>
                <td class="num"><span data-gap="stage_1" class="click" style="cursor:pointer" title="Click to view all leads received">${n(o.stage_1)}</span></td>
                <td class="num"><span data-gap="stage_2" class="click" style="cursor:pointer" title="Click to view all contacted leads">${o.contact_rate}%</span></td>
                <td class="num sp-col-no-response" style="font-weight:700"><span data-gap="no_response_leads" class="click" style="cursor:pointer" title="Click to view all No Response leads">${n(o.no_response || 0)}</span> <small>(${o.no_response_rate || 0}%)</small></td>
                <td class="num">${hrs(o.median_response_hours)}</td>
                <td class="num"><span data-gap="follow_ups" class="click" style="cursor:pointer" title="Click to view all follow-ups">${n(o.follow_ups)}</span></td>
                <td class="num"><span data-gap="visits_done" class="click" style="cursor:pointer" title="Click to view all visits done">${n(o.visits_done)}</span></td>
                <td class="num"><span data-gap="bookings" class="click" style="cursor:pointer;color:var(--sp-green);font-weight:700" title="Click to view all bookings">${n(o.bookings)}</span></td>
                <td class="num"><span data-gap="confirmed_sales" class="click" style="cursor:pointer" title="Click to view all confirmed sales">${n(o.confirmed_sales)}</span> / ${o.target_bookings ? n(o.target_bookings) : '—'}</td>
                <td></td>
                <td class="num"><span data-gap="collection" class="click" style="cursor:pointer" title="Click to view all collections">${tk(o.collection)}</span></td>
                <td class="num"><span data-gap="overdue" class="click" style="cursor:pointer" title="Click to view all overdue follow-ups">${n(o.overdue)}</span></td>
                <td></td></tr>`;
            $$('tbody tr', teamTable).forEach((tr) => tr.addEventListener('click', (ev) => {
                if (ev.target.closest('[data-gap]')) return;
                selectTeam(tr.dataset.key);
            }));
            bindGapClicks(teamTable);

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
                        <div class="sp-unassigned-box">
                            <div style="display:flex;align-items:center;gap:10px">
                                <i class="fa-solid fa-user-clock icon"></i>
                                <div>
                                    <div class="title">${n(unassignedPeriod)} Leads Not Yet Assigned by Team Leader</div>
                                    <div class="desc">Received in this period under this team, but Team Leader has not yet distributed to any executive (${n(unassignedTotal)} total unassigned).</div>
                                </div>
                            </div>
                            <span class="sp-count-chip">${n(unassignedPeriod)}</span>
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
                if (memberSortKey === 'no_response') {
                    return ((b.no_response || 0) - (a.no_response || 0)) || (b.stage_1 - a.stage_1);
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
                    rowsHtml = '<tr><td colspan="11" class="sp-empty">No members or activity in this team.</td></tr>';
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
                                <td class="num sp-col-no-response">
                                    ${n(e.no_response || 0)}
                                    <small style="font-size:10.5px">(${e.no_response_rate || 0}%)</small>
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
                                <td class="num" style="color:var(--sp-soft)">0</td>
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
                const sumNoResponse = members.reduce((s, m) => s + (m.no_response || 0), 0);
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
                        <td class="num" style="color:#b45309;font-weight:700">${n(sumNoResponse)} <small style="color:var(--sp-soft);font-weight:normal">(${t.no_response_rate || 0}%)</small></td>
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
                        <div class="s">${e.stage_1 ? `${e.contact_rate}% contacted · <b class="sp-col-no-response">${n(e.no_response || 0)} no resp</b>` : '0%'}</div>
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
                    <td class="num sp-col-no-response">${n(e.no_response || 0)}<div style="font-size:10.5px;color:var(--sp-soft)">${e.no_response_rate || 0}%</div></td>
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
                : '<tr><td colspan="14" class="sp-empty">No active executives match the filters.</td></tr>';
            $$('tbody tr[data-id]', execTable).forEach((tr) => tr.addEventListener('click', () => showExec(Number(tr.dataset.id))));

            const tfoot = $('tfoot', execTable);
            if (tfoot) {
                const sumL = list.reduce((s, e) => s + (e.stage_1 || 0), 0);
                const sumFu = list.reduce((s, e) => s + (e.follow_ups || 0), 0);
                const sumOvd = list.reduce((s, e) => s + (e.overdue || 0), 0);
                const sumUntouch = list.reduce((s, e) => s + (e.backlog_uncontacted || 0), 0);
                const sumVis = list.reduce((s, e) => s + (e.visits_done || 0), 0);
                const sumBk = list.reduce((s, e) => s + (e.bookings || 0), 0);
                const sumVal = list.reduce((s, e) => s + (e.booking_value || 0), 0);
                const sumConf = list.reduce((s, e) => s + (e.confirmed_sales || 0), 0);
                const sumColl = list.reduce((s, e) => s + (e.collection || 0), 0);

                tfoot.innerHTML = `<tr>
                    <td>Total (Active Execs)</td>
                    <td class="num"><span data-gap="stage_1" class="click" style="cursor:pointer" title="Click to view all leads received">${n(sumL)}</span></td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num"><span data-gap="follow_ups" class="click" style="cursor:pointer" title="Click to view all follow-ups">${n(sumFu)}</span></td>
                    <td class="num"><span data-gap="overdue" class="click" style="cursor:pointer" title="Click to view all overdue">${n(sumOvd)}</span></td>
                    <td class="num"><span data-gap="uncontacted" class="click" style="cursor:pointer" title="Click to view all untouched">${n(sumUntouch)}</span></td>
                    <td class="num"><span data-gap="visits_done" class="click" style="cursor:pointer" title="Click to view all visits">${n(sumVis)}</span></td>
                    <td class="num"><span data-gap="bookings" class="click" style="cursor:pointer;color:var(--sp-green);font-weight:700" title="Click to view all bookings">${n(sumBk)}</span></td>
                    <td class="num">${tk(sumVal)}</td>
                    <td class="num"><b><span data-gap="confirmed_sales" class="click" style="cursor:pointer" title="Click to view all confirmed sales">${n(sumConf)}</span></b></td>
                    <td class="num"><span data-gap="collection" class="click" style="cursor:pointer" title="Click to view all collection">${tk(sumColl)}</span></td>
                    <td class="num"></td>
                </tr>`;
            }
            bindGapClicks(execTable);
        }

        const countEl = $('#spExecCount');
        if (countEl) countEl.textContent = `${list.length} active executives`;

        const execRecon = $('#spExecRecon');
        if (execRecon) {
            const o = D.overall;
            const inactL = (D.inactiveExecutives || []).reduce((s, e) => s + (e.stage_1 || 0), 0);
            const inactBk = (D.inactiveExecutives || []).reduce((s, e) => s + (e.bookings || 0), 0);
            const inactColl = (D.inactiveExecutives || []).reduce((s, e) => s + (e.collection || 0), 0);
            const allActiveL = (D.executives || []).reduce((s, e) => s + (e.stage_1 || 0), 0);
            const allActiveBk = (D.executives || []).reduce((s, e) => s + (e.bookings || 0), 0);
            const allActiveColl = (D.executives || []).reduce((s, e) => s + (e.collection || 0), 0);
            const unassignedL = Math.max(0, o.stage_1 - allActiveL - inactL);
            const directBk = Math.max(0, o.bookings - allActiveBk - inactBk);
            const directColl = Math.max(0, o.collection - allActiveColl - inactColl);

            execRecon.innerHTML = `<i class="fa-regular fa-scale-balanced"></i> Reconciliation – Leads: Active executives <b>${n(allActiveL)}</b> + Inactive staff <b>${n(inactL)}</b> + Unassigned gap <b>${n(unassignedL)}</b> = total <b>${n(o.stage_1)}</b>.
                Bookings: Active executives <b>${n(allActiveBk)}</b> + Inactive staff <b>${n(inactBk)}</b> + Direct/Co <b>${n(directBk)}</b> = total <b>${n(o.bookings)}</b>.
                Collection: Active executives <b>${tk(allActiveColl)}</b> + Inactive/Direct <b>${tk(directColl + inactColl)}</b> = total <b>${tk(o.collection)}</b>.`;
        }
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
                        <td class="num sp-col-no-response">${n(e.no_response || 0)}</td>
                        <td class="num">${n(e.follow_ups)}</td>
                        <td class="num">${n(e.visits_done)}</td>
                        <td class="num">${n(e.bookings)}</td>
                        <td class="num">${tk(e.booking_value)}</td>
                        <td class="num"><b>${n(e.confirmed_sales)}</b></td>
                        <td class="num">${tk(e.collection)}</td>
                    </tr>`;
                }).join('')
                : '<tr><td colspan="11" class="sp-empty">No inactive executives match the filters.</td></tr>';
            $$('tbody tr[data-id]', inactTable).forEach((tr) => tr.addEventListener('click', () => showExec(Number(tr.dataset.id))));

            const inactFoot = $('tfoot', inactTable);
            if (inactFoot) {
                const iSumL = list.reduce((s, e) => s + (e.stage_1 || 0), 0);
                const iSumFu = list.reduce((s, e) => s + (e.follow_ups || 0), 0);
                const iSumVis = list.reduce((s, e) => s + (e.visits_done || 0), 0);
                const iSumBk = list.reduce((s, e) => s + (e.bookings || 0), 0);
                const iSumVal = list.reduce((s, e) => s + (e.booking_value || 0), 0);
                const iSumConf = list.reduce((s, e) => s + (e.confirmed_sales || 0), 0);
                const iSumColl = list.reduce((s, e) => s + (e.collection || 0), 0);

                inactFoot.innerHTML = `<tr>
                    <td>Total (Inactive Staff)</td>
                    <td></td>
                    <td class="num"><span data-gap="stage_1" class="click" style="cursor:pointer" title="Click to view all leads">${n(iSumL)}</span></td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num"><span data-gap="follow_ups" class="click" style="cursor:pointer" title="Click to view all follow-ups">${n(iSumFu)}</span></td>
                    <td class="num"><span data-gap="visits_done" class="click" style="cursor:pointer" title="Click to view all visits">${n(iSumVis)}</span></td>
                    <td class="num"><span data-gap="bookings" class="click" style="cursor:pointer;color:var(--sp-green);font-weight:700" title="Click to view all bookings">${n(iSumBk)}</span></td>
                    <td class="num">${tk(iSumVal)}</td>
                    <td class="num"><b><span data-gap="confirmed_sales" class="click" style="cursor:pointer" title="Click to view all confirmed sales">${n(iSumConf)}</span></b></td>
                    <td class="num"><span data-gap="collection" class="click" style="cursor:pointer" title="Click to view all collection">${tk(iSumColl)}</span></td>
                </tr>`;
            }
            bindGapClicks(inactTable);
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
        const mini = (k, v, s, gapKey) => {
            const clickAttr = gapKey ? `class="sp-mini click" data-gap="${gapKey}" data-exec-id="${e.id}" style="cursor:pointer" title="Click to view ${k}"` : 'class="sp-mini"';
            const icon = gapKey ? ' <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;opacity:0.6;margin-left:2px"></i>' : '';
            return `<div ${clickAttr}><div class="k">${k}${icon}</div><div class="v">${v}</div><div class="s">${s || ''}</div></div>`;
        };
        const html = `
            <div class="sp-mini-grid" style="grid-template-columns:repeat(3,1fr)">
                ${mini('Leads received', n(e.stage_1), `${n(e.uncontacted)} not contacted`, 'stage_1')}
                ${mini('Contact rate', e.contact_rate + '%', `${n(e.stage_2)} contacted`, 'stage_2')}
                ${mini('No Response', `${n(e.no_response || 0)} (${e.no_response_rate || 0}%)`, 'unreachable / not received', 'no_response_leads')}
                ${mini('1st response', hrs(e.median_response_hours), 'median')}
                ${mini('Follow-ups', n(e.follow_ups), `${n(e.leads_followed)} leads`, 'follow_ups')}
                ${mini('Overdue', n(e.overdue), `${n(e.due_today)} due today`, 'overdue')}
                ${mini('Untouched', n(e.backlog_uncontacted), 'open > 24h', 'uncontacted')}
                ${mini('Visits done', n(e.visits_done), `${n(e.upcoming_visits)} upcoming`, 'visits_done')}
                ${mini('Bookings', n(e.bookings), tk(e.booking_value), 'bookings')}
                ${mini('Confirmed', n(e.confirmed_sales), tk(e.confirmed_value), 'confirmed_sales')}
                ${mini('Collection', tk(e.collection), 'verified', 'collection')}
                ${mini('Onboarded', n(e.onboarded), 'this period', 'onboarded')}
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
        bindGapClicks(document.getElementById('spDrawerBody'));
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
    // ---------- quick preset pills & date filtering ----------
    const presetPills = $$('.sp-preset-pill');
    const spFrom = $('#spFrom');
    const spTo = $('#spTo');
    const spPreset = $('#spPreset');
    const spCycle = $('#spCycle');
    const filterForm = $('#spFilterForm');

    const fmtDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    if (presetPills.length && filterForm) {
        presetPills.forEach((btn) => {
            btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const p = btn.dataset.preset;
                const now = new Date();

                presetPills.forEach(b => b.classList.toggle('active', b === btn));
                if (spPreset) spPreset.value = p;

                if (p === 'today') {
                    const today = fmtDate(now);
                    if (spFrom) spFrom.value = today;
                    if (spTo) spTo.value = today;
                    if (spCycle) spCycle.value = 'preset:today';
                    filterForm.submit();
                } else if (p === 'yesterday') {
                    const yest = new Date(now.getTime() - 24 * 60 * 60 * 1000);
                    const yestStr = fmtDate(yest);
                    if (spFrom) spFrom.value = yestStr;
                    if (spTo) spTo.value = yestStr;
                    if (spCycle) spCycle.value = 'preset:yesterday';
                    filterForm.submit();
                } else if (p === 'weekly') {
                    const weekAgo = new Date(now.getTime() - 6 * 24 * 60 * 60 * 1000);
                    if (spFrom) spFrom.value = fmtDate(weekAgo);
                    if (spTo) spTo.value = fmtDate(now);
                    if (spCycle) spCycle.value = 'preset:weekly';
                    filterForm.submit();
                } else if (p === 'cycle') {
                    if (spFrom) spFrom.value = '';
                    if (spTo) spTo.value = '';
                    if (spCycle) {
                        if (spCycle.value.startsWith('preset:') || spCycle.value === 'custom') {
                            const opt = Array.from(spCycle.options).find(o => !o.value.startsWith('preset:') && o.value !== 'custom' && o.value !== 'all' && !o.value.startsWith('year:'));
                            if (opt) spCycle.value = opt.value;
                        }
                    }
                    filterForm.submit();
                } else if (p === 'custom') {
                    if (spCycle) spCycle.value = 'custom';
                    if (spFrom) {
                        spFrom.focus();
                        spFrom.style.borderColor = 'var(--sp-green)';
                        setTimeout(() => { if (spFrom) spFrom.style.borderColor = ''; }, 1200);
                    }
                }
            });
        });
    }

    // Sync date changes with Custom Range pill & cycle dropdown
    function onCustomDateChange() {
        if (spPreset) spPreset.value = 'custom';
        if (spCycle) spCycle.value = 'custom';
        presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'custom'));
    }
    if (spFrom) {
        spFrom.addEventListener('change', onCustomDateChange);
        spFrom.addEventListener('input', onCustomDateChange);
    }
    if (spTo) {
        spTo.addEventListener('change', onCustomDateChange);
        spTo.addEventListener('input', onCustomDateChange);
    }

    if (spCycle) {
        spCycle.addEventListener('change', () => {
            const val = spCycle.value;
            if (val === 'preset:today') {
                const today = fmtDate(new Date());
                if (spFrom) spFrom.value = today;
                if (spTo) spTo.value = today;
                if (spPreset) spPreset.value = 'today';
                presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'today'));
                filterForm && filterForm.submit();
            } else if (val === 'preset:yesterday') {
                const yest = fmtDate(new Date(Date.now() - 24 * 60 * 60 * 1000));
                if (spFrom) spFrom.value = yest;
                if (spTo) spTo.value = yest;
                if (spPreset) spPreset.value = 'yesterday';
                presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'yesterday'));
                filterForm && filterForm.submit();
            } else if (val === 'preset:weekly') {
                const now = new Date();
                const weekAgo = fmtDate(new Date(Date.now() - 6 * 24 * 60 * 60 * 1000));
                if (spFrom) spFrom.value = weekAgo;
                if (spTo) spTo.value = fmtDate(now);
                if (spPreset) spPreset.value = 'weekly';
                presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'weekly'));
                filterForm && filterForm.submit();
            } else if (val === 'custom') {
                if (spPreset) spPreset.value = 'custom';
                presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'custom'));
                if (spFrom) spFrom.focus();
            } else {
                // Monthly cycle, yearly, or lifetime selected
                if (spFrom) spFrom.value = '';
                if (spTo) spTo.value = '';
                if (spPreset) spPreset.value = 'cycle';
                presetPills.forEach(b => b.classList.toggle('active', b.dataset.preset === 'cycle'));
                filterForm && filterForm.submit();
            }
        });
    }

    // Custom range: require both dates
    const form = $('#spFilterForm');
    form && form.addEventListener('submit', (ev) => {
        const f = $('#spFrom') ? $('#spFrom').value : '', t = $('#spTo') ? $('#spTo').value : '';
        const cycleEl = $('#spCycle');
        const isCustom = (cycleEl && cycleEl.value === 'custom') || (spPreset && spPreset.value === 'custom') || (f || t);

        if (isCustom) {
            if ((f && !t) || (!f && t)) {
                ev.preventDefault();
                alert('Please select both start and end dates for a custom range, or clear both to use the sales cycle.');
                return;
            } else if (f && t && f > t) {
                ev.preventDefault();
                alert('Start date must be before end date.');
                return;
            }
            if (f && t) {
                if (cycleEl) cycleEl.value = 'custom';
                if (spPreset) spPreset.value = 'custom';
            }
        }
        $$('select, input', form).forEach((el) => { if (!el.value && el.name !== 'tab') el.disabled = true; });
    });
})();
