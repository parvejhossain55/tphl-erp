
    <!-- ══ PAGE: LEAVES ══ -->
   
   <div class="row">
    <div class="card__wrapper">
        <div class="section-header">
            <div class="section-title">Leave Management</div>
            <div class="section-sub">Track and manage your leave quota</div>
        </div>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon" style="background:rgba(52,211,153,.12)">
                            <i class="bi bi-hospital" style="color:var(--accent3)"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem;font-weight:700">Medical Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Total: 12 days/year</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent5);font-family:'Space Mono',monospace">3</div>
                            <div style="font-size:.7rem;color:var(--muted)">Used</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent3);font-family:'Space Mono',monospace">9</div>
                            <div style="font-size:.7rem;color:var(--muted)">Remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap mt-3">
                        <div class="att-bar" style="width:25%;background:var(--accent3)"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon" style="background:rgba(79,140,255,.12)">
                            <i class="bi bi-cup-hot-fill" style="color:var(--accent)"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem;font-weight:700">Casual Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Total: 10 days/year</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent5);font-family:'Space Mono',monospace">2</div>
                            <div style="font-size:.7rem;color:var(--muted)">Used</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">8</div>
                            <div style="font-size:.7rem;color:var(--muted)">Remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap mt-3">
                        <div class="att-bar" style="width:20%;background:var(--accent)"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon" style="background:rgba(167,139,250,.12)">
                            <i class="bi bi-airplane-fill" style="color:var(--accent2)"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem;font-weight:700">Annual Leave</div>
                            <div style="font-size:.72rem;color:var(--muted)">Total: 20 days/year</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent5);font-family:'Space Mono',monospace">8</div>
                            <div style="font-size:.7rem;color:var(--muted)">Used</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1.6rem;font-weight:700;color:var(--accent2);font-family:'Space Mono',monospace">12</div>
                            <div style="font-size:.7rem;color:var(--muted)">Remaining</div>
                        </div>
                    </div>
                    <div class="att-bar-wrap mt-3">
                        <div class="att-bar" style="width:40%;background:var(--accent2)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave History -->
        <div class="stat-card">
            <div class="section-title mb-3">Leave History</div>
            <div class="table-responsive">
                <table class="leave-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="color:var(--muted)">01</td>
                            <td><span class="leave-badge badge-present">Medical</span></td>
                            <td>Jan 14</td>
                            <td>Jan 15</td>
                            <td>2</td>
                            <td style="color:var(--muted)">Flu & fever</td>
                            <td><span class="leave-badge badge-present">Approved</span></td>
                        </tr>
                        <tr>
                            <td style="color:var(--muted)">02</td>
                            <td><span class="leave-badge badge-leave">Casual</span></td>
                            <td>Mar 5</td>
                            <td>Mar 5</td>
                            <td>1</td>
                            <td style="color:var(--muted)">Personal work</td>
                            <td><span class="leave-badge badge-present">Approved</span></td>
                        </tr>
                        <tr>
                            <td style="color:var(--muted)">03</td>
                            <td><span class="leave-badge badge-leave">Annual</span></td>
                            <td>Jun 1</td>
                            <td>Jun 5</td>
                            <td>5</td>
                            <td style="color:var(--muted)">Vacation</td>
                            <td><span class="leave-badge badge-present">Approved</span></td>
                        </tr>
                        <tr>
                            <td style="color:var(--muted)">04</td>
                            <td><span class="leave-badge badge-present">Medical</span></td>
                            <td>Aug 22</td>
                            <td>Aug 22</td>
                            <td>1</td>
                            <td style="color:var(--muted)">Dental appointment</td>
                            <td><span class="leave-badge badge-present">Approved</span></td>
                        </tr>
                        <tr>
                            <td style="color:var(--muted)">05</td>
                            <td><span class="leave-badge badge-leave">Annual</span></td>
                            <td>Dec 10</td>
                            <td>Dec 12</td>
                            <td>3</td>
                            <td style="color:var(--muted)">Family event</td>
                            <td><span class="leave-badge badge-late">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>