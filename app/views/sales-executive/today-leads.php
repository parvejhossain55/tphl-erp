<?php
    $page_title = "User Dashboard ";

    $totalDuplicatesCount = 0;
    foreach ($todayLeads as $lead) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
        $cleanEmail = trim(strtolower($lead['email'] ?? ''));
        $leadDuplicates = [];
        $dupIds = [];
        if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
            foreach ($phoneMap[$cleanPhone] as $item) {
                if (!in_array($item['id'], $dupIds)) { $leadDuplicates[] = $item; $dupIds[] = $item['id']; }
            }
        }
        if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
            foreach ($emailMap[$cleanEmail] as $item) {
                if (!in_array($item['id'], $dupIds)) { $leadDuplicates[] = $item; $dupIds[] = $item['id']; }
            }
        }
        if (count($leadDuplicates) > 1) {
            $totalDuplicatesCount++;
        }
    }
    ?>

   <div class="row">
       <div class="card__wrapper">

           <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
               <div>
                   <h4 class="mb-0">Today’s Leads</h4>
                   <small class="text-muted">All leads created today</small>
               </div>

           </div>
       </div>

       <div class="row">
           <div class="desktop-only">
               <div class="col-xxl-12">
                   <div class="card__wrapper">
                       <div class="table__wrapper table-responsive">
                           <table id="todayLeadsTable" class="table no-border-table mb-20 multiple_tables">
                               <thead>
                                   <tr class="table__title table__sort">
                                       <th style="min-width:60px;">S/L</th>
                                       <th style="min-width: 120px;">Lead Date</th>
                                       <th style="min-width: 130px;">Duplicate</th>
                                       <th style="min-width: 160px;">Lead & Job Title/company</th>
                                       <th style="min-width: 150px;">Best Time to Contact</th>
                                       <th style="min-width: 140px;">Address & City</th>
                                       <th style="min-width: 180px;">Project Campaign</th>
                                       <th style="min-width: 200px;">Last Follow Up Project</th>
                                       <th style="min-width: 160px;">Change Client Action</th>
                                       <th style="min-width: 160px;" class="text-center">Call Status</th>
                                       <th style="min-width: 160px;" class="text-center">Communication Type</th>
                                       <th style="min-width: 160px;" class="text-center">Lead Status</th>
                                       <th style="min-width: 130px;" class="text-center">Follow Up Status</th>
                                       <th style="min-width: 250px;" class="text-center">Instant Call Note</th>
                                       <th style="min-width: 250px;" class="text-center">Last Follow Up Note</th>
                                       <th style="min-width: 140px;">Next Follow-Up</th>
                                       <th style="min-width: 180px;">Action</th>
                                   </tr>
                               </thead>
                               <tbody class="table__body">
                                   <?php foreach ($todayLeads as $key => $lead): ?>
                                       <?php
                                        $displayName = !empty($lead['campaign_name'])
                                            ? $lead['campaign_name']
                                            : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                                        if (!empty($lead['project_category_name'])) {
                                            $displayName .= ' (' . $lead['project_category_name'] . ')';
                                        }
                                        $plainText = strip_tags($displayName ?? '');

                                        $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
                                        $cleanEmail = trim(strtolower($lead['email'] ?? ''));
                                        $leadDuplicates = [];
                                        $dupIds = [];
                                        if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
                                            foreach ($phoneMap[$cleanPhone] as $item) {
                                                if (!in_array($item['id'], $dupIds)) {
                                                    $leadDuplicates[] = $item;
                                                    $dupIds[] = $item['id'];
                                                }
                                            }
                                        }
                                        if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
                                            foreach ($emailMap[$cleanEmail] as $item) {
                                                if (!in_array($item['id'], $dupIds)) {
                                                    $leadDuplicates[] = $item;
                                                    $dupIds[] = $item['id'];
                                                }
                                            }
                                        }
                                        $isDup = count($leadDuplicates) > 1;
                                        ?>
                                       <tr id="lead-<?= $lead['id'] ?>" data-id="<?= $lead['id'] ?>" data-is-duplicate="<?= $isDup ? '1' : '0' ?>">
                                           <!-- S/L -->
                                           <td><?= $key + 1 ?></td>

                                           <!-- Lead Date -->
                                           <td><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>

                                            <!-- Duplicate Status -->
                                            <td>
                                                <?php if ($isDup): ?>
                                                    <span class="badge bg-danger cursor-pointer duplicate-badge-click" 
                                                          data-duplicates="<?= htmlspecialchars(json_encode($leadDuplicates), ENT_QUOTES, 'UTF-8') ?>"
                                                          data-current-lead-id="<?= $lead['id'] ?>"
                                                          style="cursor: pointer;"
                                                          title="Click to view duplicate history">
                                                        Duplicate (<?= count($leadDuplicates) ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Unique</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Lead Name + WhatsApp + Job/Company -->
                                            <td>
                                                <strong><?= htmlspecialchars($lead['full_name'] ?? '') ?></strong>
                                                <?php
                                                 $phone = $lead['phone_number'] ?? $lead['phone'] ?? '';
                                                 $phone = preg_replace('/[^0-9]/', '', $phone);
                                                 if (substr($phone, 0, 2) === '01') {
                                                     $phone = '880' . substr($phone, 1);
                                                 }
                                                 ?>
                                                <div class="mt-1">
                                                    <a href="https://wa.me/<?= $phone ?>" target="_blank"><?= $phone ?></a>
                                                </div>
                                                <?php
                                                $jt = !empty($lead['job_title']) ? $lead['job_title'] : (!empty($lead['designation']) ? $lead['designation'] : '');
                                                $co = !empty($lead['company_name']) ? $lead['company_name'] : (!empty($lead['company']) ? $lead['company'] : '');
                                                if ($jt !== '' || $co !== ''):
                                                ?>
                                                <div class="text-muted fs-11 mt-1">
                                                    <i class="bi bi-briefcase me-1"></i>
                                                    <?= htmlspecialchars($jt) ?><?= ($jt !== '' && $co !== '') ? ' at ' : '' ?><strong><?= htmlspecialchars($co) ?></strong>
                                                </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Best Time to Contact -->
                                            <td>
                                                <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you'] ?? '—') ?>
                                            </td>

                                            <!-- Address & City -->
                                            <td>
                                                <?php 
                                                $addrParts = [];
                                                if (!empty($lead['country'])) $addrParts[] = trim($lead['country']);
                                                if (!empty($lead['address'])) $addrParts[] = trim($lead['address']);
                                                if (!empty($lead['city'])) $addrParts[] = trim($lead['city']);
                                                $addr = implode(', ', $addrParts);
                                                ?>
                                                <span class="fs-12"><?= htmlspecialchars($addr !== '' ? $addr : '—') ?></span>
                                            </td>

                                           <!-- Project Campaign -->
                                           <td>
                                               <div class="d-flex align-items-center gap-2 flex-nowrap">
                                                   <span class="project-badge mb-0">
                                                       <i class="bi bi-building"></i>
                                                       <?= truncateText($displayName, 15) ?>
                                                   </span>
                                                   <?php if (mb_strlen($plainText) > 15): ?>
                                                       <button type="button"
                                                           id="viewCampaignBtn<?= $lead['id'] ?>"
                                                           class="btn btn-sm btn-outline-primary flex-shrink-0"
                                                           data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#viewProjectCampaignName">View
                                                       </button>
                                                   <?php endif; ?>
                                               </div>
                                           </td>

                                           <!-- Last Follow-Up Project -->
                                           <td>
                                               <?php if (!empty($lead['project_name'])): ?>
                                                   <div class="bd-badge-custom">
                                                       <div class="fw-bold"><?= htmlspecialchars($lead['project_name']) ?></div>
                                                       <div class="mt-1 small" style="opacity:0.9;border-top:1px solid rgba(255,255,255,0.2);padding-top:2px;">
                                                           <strong>Flat:</strong> <?= !empty($lead['flat_name']) ? htmlspecialchars($lead['flat_name']) : 'N/A' ?>
                                                       </div>
                                                   </div>
                                               <?php else: ?>
                                                   <div class="bd-badge-custom bd-badge-secondary-custom">No Follow Data</div>
                                               <?php endif; ?>
                                           </td>

                                            <!-- Change Client Action -->
                                            <td>
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>" class="m-0 p-0">
                                                    <select name="last_lead_action"
                                                        class="form-select form-select-sm last-action-select last-action-select-ajax <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                                        data-lead-id="<?= $lead['id'] ?>">
                                                        <option value="">Update action</option>
                                                        <option value="contacted" <?= $lead['last_lead_action'] == 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                                        <option value="new" <?= $lead['last_lead_action'] == 'new' ? 'selected' : '' ?>>New</option>
                                                        <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                                                        <option value="follow up" <?= $lead['last_lead_action'] == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                                                        <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>Visited</option>
                                                        <option value="cancel" <?= $lead['last_lead_action'] == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                                    </select>
                                                </form>
                                            </td>

                                           <!-- Call Status -->
                                           <?php
                                            $callStatus = $lead['call_status'] ?? 'not_called';
                                            $callClass = $callStatus == 'received' ? 'bg-received' : ($callStatus == 'not_received' ? 'bg-not-received' : '');
                                            ?>
                                           <td class="call-status-cell">
                                               <select class="call-status <?= $callClass ?>" data-id="<?= $lead['id'] ?>">
                                                   <option value="not_called" <?= $callStatus == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                                                   <option value="called" <?= $callStatus == 'called'     ? 'selected' : '' ?>>Called</option>
                                                   <option value="received" <?= $callStatus == 'received'   ? 'selected' : '' ?>>Received</option>
                                                   <option value="not_received" <?= $callStatus == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                                               </select>
                                           </td>

                                           <!-- Communication Type -->
                                            <?php
                                             $commType = $lead['communication_type'] ?? 'Not Select';
                                             $commClass = '';
                                             if (in_array($commType, ['WA Messaging', 'WA Conversion', 'Wa Call', 'Imo Massage', 'Imo Call', 'Phone Call', 'Phone Massage'])) {
                                                 $commClass = 'bg-received';
                                             } elseif (in_array($commType, ['Invalid Number', 'No Wa /No Imo', 'Number Off'])) {
                                                 $commClass = 'bg-not-received';
                                             }
                                             ?>
                                            <td class="call-status-cell">
                                                <select class="communication-status <?= $commClass ?> form-select form-control" data-id="<?= $lead['id'] ?>">
                                                    <option value="">Select type</option>
                                                    <option value="WA Messaging" <?= $commType == 'WA Messaging'  ? 'selected' : '' ?>>WA Messaging</option>
                                                    <option value="WA Conversion" <?= $commType == 'WA Conversion' ? 'selected' : '' ?>>WA Conversion</option>
                                                    <option value="Wa Call" <?= $commType == 'Wa Call' ? 'selected' : '' ?>>Wa Call</option>
                                                    <option value="Imo Massage" <?= $commType == 'Imo Massage' ? 'selected' : '' ?>>Imo Massage</option>
                                                    <option value="Imo Call" <?= $commType == 'Imo Call' ? 'selected' : '' ?>>Imo Call</option>
                                                    <option value="Phone Call" <?= $commType == 'Phone Call' ? 'selected' : '' ?>>Phone Call</option>
                                                    <option value="Phone Massage" <?= $commType == 'Phone Massage' ? 'selected' : '' ?>>Phone Massage</option>
                                                    <option value="Invalid Number" <?= $commType == 'Invalid Number' ? 'selected' : '' ?>>Invalid Number</option>
                                                    <option value="No Wa /No Imo" <?= $commType == 'No Wa /No Imo' ? 'selected' : '' ?>>No Wa /No Imo</option>
                                                    <option value="Number Off" <?= $commType == 'Number Off' ? 'selected' : '' ?>>Number Off</option>
                                                </select>
                                            </td>

                                           <!-- Lead Status -->
                                           <?php
                                            $statusVal = $lead['lead_type'] ?? 'Pending';
                                            $statusClass = '';
                                            if (in_array($statusVal, ['Prospect', 'High Prospect'])) $statusClass = 'bg-received';
                                            elseif (in_array($statusVal, ['Not Interested', "Don't Call Again", 'Cold', 'Refund'])) $statusClass = 'bg-not-received';
                                            ?>
                                           <td>
                                               <select class="lead-status <?= $statusClass ?> form-control select-form" data-id="<?= $lead['id'] ?>">
                                                   <?php
                                                    $statuses = ['No Response', 'Pending', 'Prospect', 'High Prospect', 'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 'Project Transferred', 'Not Interested', "Don't Call Again", 'Refund', 'Future', 'Cold', 'Hot', 'Warm'];
                                                    foreach ($statuses as $st) {
                                                        echo "<option value=\"$st\"" . ($statusVal == $st ? ' selected' : '') . ">$st</option>";
                                                    }
                                                    ?>
                                               </select>
                                           </td>

                                           <!-- Follow Up Status -->
                                           <td class="text-center">
                                               <?php
                                                $fuStatus = $lead['last_follow_up_status'] ?? null;
                                                $nextDate = $lead['next_follow_up_date'] ?? null;
                                                $todayD   = date('Y-m-d');
                                                if ($fuStatus === 'completed') {
                                                    $fuBadgeClass = 'bg-success';
                                                    $fuLabel = '&#x2714; Completed';
                                                } elseif ($fuStatus === 'missed') {
                                                    $fuBadgeClass = 'bg-danger';
                                                    $fuLabel = '&#x2718; Missed';
                                                } elseif ($fuStatus === 'canceled') {
                                                    $fuBadgeClass = 'bg-secondary';
                                                    $fuLabel = 'Canceled';
                                                } elseif ($fuStatus === 'pending' && !empty($nextDate)) {
                                                    if ($nextDate < $todayD) {
                                                        $fuBadgeClass = 'bg-danger';
                                                        $fuLabel = '&#x26A0; Overdue';
                                                    } elseif ($nextDate === $todayD) {
                                                        $fuBadgeClass = 'bg-warning text-dark';
                                                        $fuLabel = 'Due Today';
                                                    } else {
                                                        $fuBadgeClass = 'bg-info text-dark';
                                                        $fuLabel = 'Upcoming';
                                                    }
                                                } elseif ($fuStatus === 'pending') {
                                                    $fuBadgeClass = 'bg-warning text-dark';
                                                    $fuLabel = 'Pending';
                                                } else {
                                                    $fuBadgeClass = 'bg-light text-muted border';
                                                    $fuLabel = 'No Follow Up';
                                                }
                                                ?>
                                               <span class="badge <?= $fuBadgeClass ?>" style="font-size:11px;padding:5px 8px;white-space:nowrap;"><?= $fuLabel ?></span>
                                           </td>

                                            <!-- First Call Note -->
                                            <td class="note-cell">
                                                <div class="note-wrapper" style="display: flex; gap: 5px; align-items: center;">
                                                    <textarea class="call-note" data-id="<?= $lead['id'] ?>"
                                                        placeholder="Write call note..."
                                                        style="min-height: 38px; max-height: 38px; font-size: 0.8rem; padding: 5px 8px; border: 1px solid #ddd; border-radius: 5px; resize: none; flex: 1;"><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                                                    <button type="button" class="btn btn-success btn-sm note-save-btn" data-id="<?= $lead['id'] ?>" style="padding: 5px 10px; font-size: 0.75rem; height: 38px;">Save</button>
                                                </div>
                                            </td>

                                            <!-- Communicate Note (last follow-up, readonly) -->
                                            <td class="note-cell">
                                                <div class="note-wrapper">
                                                    <textarea class="last-followup-note"
                                                        placeholder="No follow-up note yet..."
                                                        readonly
                                                        style="background:#f8f9fa;cursor:default; min-height: 38px; max-height: 38px; font-size: 0.8rem; padding: 5px 8px; border: 1px solid #ddd; border-radius: 5px; resize: none; flex: 1;"><?= htmlspecialchars($lead['last_follow_up_note'] ?? '') ?></textarea>
                                                </div>
                                            </td>

                                           <!-- Next Follow-Up -->
                                           <td>
                                               <div class="next-followup-badge">
                                                   <i class="bi bi-calendar-check me-1"></i>
                                                   <?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?>
                                               </div>
                                           </td>

                                           <!-- Action -->
                                           <td>
                                               <div class="d-flex align-items-center gap-1">
                                                   <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1 justify-content-center" style="white-space:nowrap;">
                                                       <span>Follow Up</span>
                                                       <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                                           <span class="action-badge"><?= $followUpCounts[$lead['id']] ?></span>
                                                       <?php endif; ?>
                                                   </a>
                                                   <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>" class="btn btn-secondary btn-sm d-flex align-items-center gap-1 justify-content-center" style="white-space:nowrap;">
                                                       <span>Booking</span>
                                                       <?php if (!empty($bookingCount[$lead['id']])): ?>
                                                           <span class="badge bg-white text-dark" style="font-size:10px;"><?= $bookingCount[$lead['id']] ?></span>
                                                       <?php endif; ?>
                                                   </a>
                                                   <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>" title="View Lead" style="white-space:nowrap;">
                                                       <i class="fa-regular fa-eye"></i>
                                                   </a>
                                               </div>

                                           </td>
                                       </tr>
                                   <?php endforeach ?>
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>
           </div>

           <div class="mobile-only">
               <?php foreach ($todayLeads as $key => $lead): ?>
                   <?php
                    $displayName = !empty($lead['campaign_name'])
                        ? $lead['campaign_name']
                        : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                    if (!empty($lead['project_category_name'])) {
                        $displayName .= ' (' . $lead['project_category_name'] . ')';
                    }
                    $plainText = strip_tags($displayName ?? '');

                    // Clean phone for call link
                    $phone = !empty($lead['phone_number']) ? preg_replace('/[^0-9+]/', '', $lead['phone_number']) : '';

                    $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
                    $cleanEmail = trim(strtolower($lead['email'] ?? ''));
                    $leadDuplicates = [];
                    $dupIds = [];
                    if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
                        foreach ($phoneMap[$cleanPhone] as $item) {
                            if (!in_array($item['id'], $dupIds)) {
                                $leadDuplicates[] = $item;
                                $dupIds[] = $item['id'];
                            }
                        }
                    }
                    if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
                        foreach ($emailMap[$cleanEmail] as $item) {
                            if (!in_array($item['id'], $dupIds)) {
                                $leadDuplicates[] = $item;
                                $dupIds[] = $item['id'];
                            }
                        }
                    }
                    $isDup = count($leadDuplicates) > 1;
                    ?>
                   <div id="lead-<?= $lead['id'] ?>" class="lead-card mb-3 p-2 border rounded" data-id="<?= $lead['id'] ?>" data-is-duplicate="<?= $isDup ? '1' : '0' ?>">
                       <!-- Header: Lead Name + Phone + Job/Company/Best Contact Time -->
                       <div class="d-flex justify-content-between align-items-center">
                           <div class="">
                               <input type="checkbox" class="selectedIdMobile me-2 d-none" data-id="<?= $lead['id'] ?>">
                               <span class="lead-name fw-bold"><?= htmlspecialchars($lead['full_name'] ?? 'N/A') ?></span>
                               <?php if ($isDup): ?>
                                   <span class="badge bg-danger cursor-pointer duplicate-badge-click" 
                                         data-duplicates="<?= htmlspecialchars(json_encode($leadDuplicates), ENT_QUOTES, 'UTF-8') ?>"
                                         data-current-lead-id="<?= $lead['id'] ?>"
                                         style="cursor: pointer; font-size: 10px; padding: 2px 4px;"
                                         title="Click to view duplicate history">
                                       Dup (<?= count($leadDuplicates) ?>)
                                   </span>
                               <?php endif; ?>
                           </div>
                           <div>
                               <?php if ($phone): ?>
                                   <a href="tel:<?= $phone ?>" class="text-decoration-none fw-bold me-2"><?= htmlspecialchars($phone) ?></a>
                                   <a href="https://wa.me/<?= $phone ?>" target="_blank" class="text-success">
                                       <i class="bi bi-whatsapp"></i>
                                   </a>
                               <?php else: ?>
                                   N/A
                               <?php endif; ?>
                           </div>
                       </div>
                       <?php
                        $jt = !empty($lead['job_title']) ? $lead['job_title'] : (!empty($lead['designation']) ? $lead['designation'] : '');
                        $co = !empty($lead['company_name']) ? $lead['company_name'] : (!empty($lead['company']) ? $lead['company'] : '');
                        if ($jt !== '' || $co !== ''):
                        ?>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-briefcase me-1"></i>
                            <?= htmlspecialchars($jt) ?><?= ($jt !== '' && $co !== '') ? ' at ' : '' ?><strong><?= htmlspecialchars($co) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($lead['when_is_the_best_time_to_contact_you'])): ?>
                        <div class="text-info small mt-1">
                            <i class="bi bi-clock me-1"></i>Best Contact Time: <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Address & City -->
                        <?php 
                        $addrParts = [];
                        if (!empty($lead['country'])) $addrParts[] = trim($lead['country']);
                        if (!empty($lead['address'])) $addrParts[] = trim($lead['address']);
                        if (!empty($lead['city'])) $addrParts[] = trim($lead['city']);
                        $mAddr = implode(', ', $addrParts);
                        ?>
                        <?php if ($mAddr): ?>
                        <div class="mt-1">
                            <i class="bi bi-geo-alt-fill text-muted me-1" style="font-size:11px"></i>
                            <span class="fs-11 text-muted"><?= htmlspecialchars($mAddr) ?></span>
                        </div>
                        <?php endif; ?>

                       <!-- Status -->
                       <div class="mt-2">
                           <label class="d-block mb-1">Call Status</label>
                           <select class="call-status w-100" data-id="<?= $lead['id'] ?>">
                               <option value="not_called" <?= $lead['call_status'] == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                               <option value="called" <?= $lead['call_status'] == 'called' ? 'selected' : '' ?>>Called</option>
                               <option value="received" <?= $lead['call_status'] == 'received' ? 'selected' : '' ?>>Received</option>
                               <option value="not_received" <?= $lead['call_status'] == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                           </select>
                       </div>

                       <!-- Follow Up & Booking -->
                       <div class="d-flex gap-2 flex-wrap mt-2">
                           <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 d-flex justify-content-between align-items-center">
                               <span>Follow Up</span>
                               <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                   <span class="badge bg-secondary"><?= $followUpCounts[$lead['id']] ?></span>
                               <?php endif; ?>
                           </a>


                       </div>

                       <!-- Project Info -->
                       <div class="project-detail mt-2">
                           <div class="info-label flex-shrink-0">Project</div>
                           <div class="d-flex align-items-center gap-2 mt-1">
                               <span class="project-badge mb-0">
                                   <i class="bi bi-building"></i>
                                   <?= truncateText($displayName, 15) ?>
                               </span>
                               <?php if (mb_strlen($plainText) > 15): ?>
                                   <button type="button"
                                       id="viewCampaignBtn<?= $lead['id'] ?>"
                                       class="btn btn-sm btn-outline-primary ms-3 w-auto px-1 py-0"
                                       data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                       data-bs-toggle="modal"
                                       data-bs-target="#viewProjectCampaignName"
                                       title="View Project">View
                                   </button>
                               <?php endif; ?>
                           </div>
                       </div>

                       <!-- Next Follow-Up -->
                       <div class="mt-2">
                           <label class="d-block mb-1">Next Follow-Up</label>
                           <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">
                               <i class="bi bi-calendar2-check me-1"></i>
                               <?= !empty($lead['next_follow_up_date']) && $lead['next_follow_up_date'] !== '0000-00-00'
                                    ? formatDate($lead['next_follow_up_date'])
                                    : 'No Schedule' ?>
                           </span>
                       </div>

                       <!-- Lead Status -->
                       <div class="mt-2">
                           <label class="d-block mb-1">Lead Status</label>
                           <?php $leadType = $lead['lead_type'] ?? '-'; ?>
                           <span class="badge bg-secondary rounded-pill px-2 py-1" style="font-size:11px;">
                               <?= htmlspecialchars($leadType) ?>
                           </span>
                       </div>

                       <!-- Follow Up Status -->
                       <div class="mt-2">
                           <label class="d-block mb-1">Follow Up Status</label>
                           <?php
                            $fuStatus = $lead['last_follow_up_status'] ?? null;
                            $nextDate = $lead['next_follow_up_date'] ?? null;
                            $todayD   = date('Y-m-d');
                            if ($fuStatus === 'completed') {
                                $fuBadgeClass = 'bg-success';
                                $fuLabel = '&#x2714; Completed';
                            } elseif ($fuStatus === 'missed') {
                                $fuBadgeClass = 'bg-danger';
                                $fuLabel = '&#x2718; Missed';
                            } elseif ($fuStatus === 'canceled') {
                                $fuBadgeClass = 'bg-secondary';
                                $fuLabel = 'Canceled';
                            } elseif ($fuStatus === 'pending' && !empty($nextDate)) {
                                if ($nextDate < $todayD) {
                                    $fuBadgeClass = 'bg-danger';
                                    $fuLabel = '&#x26A0; Overdue';
                                } elseif ($nextDate === $todayD) {
                                    $fuBadgeClass = 'bg-warning text-dark';
                                    $fuLabel = 'Due Today';
                                } else {
                                    $fuBadgeClass = 'bg-info text-dark';
                                    $fuLabel = 'Upcoming';
                                }
                            } elseif ($fuStatus === 'pending') {
                                $fuBadgeClass = 'bg-warning text-dark';
                                $fuLabel = 'Pending';
                            } else {
                                $fuBadgeClass = 'bg-light text-muted border';
                                $fuLabel = 'No Follow Up';
                            }
                            ?>
                           <span class="badge <?= $fuBadgeClass ?>" style="font-size:11px; padding:5px 8px; white-space:nowrap;">
                               <?= $fuLabel ?>
                           </span>
                       </div>

                       <!-- Best Time to Contact -->
                       <div class="mt-2">
                           <label class="d-block mb-1">Best Time to Contact</label>
                           <span><?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you'] ?? 'N/A') ?></span>
                       </div>



                        <!-- First Call Note (editable) -->
                        <div class="mt-2">
                            <label class="d-block mb-1">First Call Note</label>
                            <textarea class="form-control call-note" data-id="<?= $lead['id'] ?>" placeholder="Write call note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                            <button type="button" class="btn btn-sm btn-success mt-1 note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
                        </div>

                        <!-- Last Follow Up Note (read-only) -->
                        <div class="mt-2">
                            <label class="d-block mb-1">Last Follow Up Note</label>
                            <textarea class="form-control" rows="2"
                                placeholder="No follow-up note yet..."
                                readonly
                                style="background:#f8f9fa; cursor:default; font-size:12px;"><?= htmlspecialchars($lead['last_follow_up_note'] ?? '') ?></textarea>
                        </div>
                   </div>
               <?php endforeach; ?>
           </div>


           <!-- view full rule description -->
           <div id="viewProjectCampaignName" class="modal custom-modal fade">
               <div class="modal-dialog modal-dialog-centered modal-lg">
                   <div class="modal-content">

                       <div class="modal-header">
                           <h5 class="modal-title">Project Campaing Name</h5>
                           <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                               <i class="fa-solid fa-xmark-large"></i>
                           </button>
                       </div>

                       <div class="modal-body">
                           <textarea id="view_campaign_id" class="form-control" rows="2" readonly></textarea>

                       </div>

                   </div>
               </div>
           </div>
       </div>
       <script>
           document.querySelectorAll('[id^="viewCampaignBtn"]').forEach(function(btn) {
               btn.addEventListener('click', function() {
                   //From data-description  full text 
                   const description = this.getAttribute('data-description');
                   // textarea  set 
                   document.getElementById('view_campaign_id').value = description;
               });
           });
       </script>
       <script>
            if (typeof base_url === 'undefined') {
                var base_url = "<?= $GLOBALS['base_url'] ?>";
            }
           document.querySelectorAll('.call-btn').forEach(btn => {
               btn.addEventListener('click', function() {
                   let leadId = this.dataset.id;
                   let phone = this.dataset.phone;

                   // Mobile call
                   window.location.href = 'tel:' + phone;

                   // Update call status = called
                   fetch('<?= $GLOBALS['base_url'] ?>/update-call-status', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/x-www-form-urlencoded'
                       },
                       body: 'lead_id=' + leadId + '&call_status=called'
                   });
               });
           });

           // received or not received 
           document.querySelectorAll('.call-status').forEach(select => {
               select.addEventListener('change', function() {
                   fetch('<?= $base_url ?>/update-call-status', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/x-www-form-urlencoded'
                       },
                       body: 'lead_id=' + this.dataset.id + '&call_status=' + this.value
                   });
               });
           });
           // Communication type update
           document.querySelectorAll('.communication-status').forEach(select => {
               select.addEventListener('change', function() {
                   fetch(base_url + '/update-communication-type', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/x-www-form-urlencoded'
                       },
                       body: 'lead_id=' + this.dataset.id + '&communication_type=' + this.value
                   });
               });
           });

           // lead quality
           document.querySelectorAll('.lead-quality').forEach(select => {
               select.addEventListener('change', function() {

                   fetch(base_url + '/update-lead-quality', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/x-www-form-urlencoded'
                           },
                           body: 'lead_id=' + this.dataset.id + '&lead_quality=' + encodeURIComponent(this.value)
                       })
                       .then(res => res.json())
                       .then(data => {
                           console.log(data);
                       })
                       .catch(err => {
                           console.error(err);
                       });

               });
           });


            document.querySelectorAll('.note-save-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const leadId = this.dataset.id;
                    const textarea = this.parentElement.querySelector('.call-note');
                    saveNote(leadId, textarea.value, textarea);
                });
            });

           document.querySelectorAll('.call-note').forEach(textarea => {
               textarea.addEventListener('keydown', function(e) {
                   if (e.key === 'Enter' && !e.shiftKey) { // shift+enter for newline
                       e.preventDefault(); // avoid new line
                       saveNote(this.dataset.id, this.value, this);
                   }
               });
           });

           function saveNote(leadId, note, el) {
                const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-call-notes';
                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'lead_id=' + encodeURIComponent(leadId) + '&call_note=' + encodeURIComponent(note)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            el.style.borderColor = '#198754'; // success hint
                            setTimeout(() => el.style.borderColor = '#ddd', 800);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Note saved successfully!',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                            }
                        } else {
                            throw new Error(data.error || 'Server returned failure');
                        }
                    })
                    .catch(err => {
                        console.error('Error saving note:', err);
                        el.style.borderColor = '#dc3545'; // error hint
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to save note!',
                                text: err.message,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        }
                    });
            }

            // Initialize DataTable with page restoration and scroll highlighting
            const urlParams = new URLSearchParams(window.location.search);
            const pageFromUrl = parseInt(urlParams.get('page') || '1', 10);
            const pageLength = 10;
            const displayStart = (pageFromUrl > 1) ? (pageFromUrl - 1) * pageLength : 0;

            const todayTable = $('#todayLeadsTable').DataTable({
                pageLength: pageLength,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All']
                ],
                displayStart: displayStart,
                order: [],
                drawCallback: function () {
                    if (!window.hasScrolledToTarget) {
                        const hash = window.location.hash;
                        if (hash && hash.startsWith('#lead-')) {
                            const targetEl = $(hash);
                            if (targetEl.length > 0) {
                                window.hasScrolledToTarget = true;
                                $('html, body').animate({
                                    scrollTop: targetEl.offset().top - 100
                                }, 500);
                            }
                        }
                    }
                }
            });

            todayTable.on('page.dt', function () {
                const info = todayTable.page.info();
                const page = info.page + 1;
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.history.replaceState(null, '', url.toString());
            });

            // AJAX Lead Last Action Update
            $(document).on('change', '.last-action-select-ajax', function() {
                const selectEl = $(this);
                const leadId = selectEl.data('lead-id');
                const action = selectEl.val();
                const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-last_leadAction/' + leadId;

                const formData = new URLSearchParams();
                formData.append('last_lead_action', action);
                formData.append('ajax', '1');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Remove existing classes starting with 'action-'
                        selectEl.removeClass(function (index, className) {
                            return (className.match(/(^|\s)action-\S+/g) || []).join(' ');
                        });
                        // Add new action class
                        const classVal = action.replace(' ', '_');
                        selectEl.addClass('action-' + (classVal || 'not_called'));

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: data.message || 'Action updated successfully!',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(err => {
                    console.error('Error updating action:', err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to update action!',
                            text: err.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                    setTimeout(() => location.reload(), 1500);
                });
            });
        </script>

<!-- Duplicate Leads Details Modal -->
<div id="duplicateLeadsModal" class="modal custom-modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-clone me-2"></i>Duplicate Lead History</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Below are the leads assigned to you with the same phone number or email address.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="duplicateLeadsModalTable">
                        <thead>
                            <tr class="bg-light">
                                <th>Lead ID</th>
                                <th>Name</th>
                                <th>Assigned Date</th>
                                <th>Project</th>
                                <th>Action Status</th>
                                <th>Lead Status</th>
                            </tr>
                        </thead>
                        <tbody id="duplicateLeadsModalBody">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.duplicate-badge-click', function() {
            const duplicates = $(this).data('duplicates');
            const currentLeadId = $(this).data('current-lead-id');
            let html = '';
            if (duplicates && duplicates.length > 0) {
                duplicates.forEach(function(item) {
                    const isCurrent = (item.id == currentLeadId) ? 'table-warning fw-bold' : '';
                    const currentBadge = (item.id == currentLeadId) ? ' <span class="badge bg-warning text-dark ms-1">Current</span>' : '';
                    html += `
                        <tr class="${isCurrent}">
                            <td>#${item.id}${currentBadge}</td>
                            <td>${escapeHtml(item.full_name)}</td>
                            <td>${item.created_at}</td>
                            <td>${escapeHtml(item.project_name)}</td>
                            <td><span class="badge bg-secondary">${escapeHtml(item.last_lead_action)}</span></td>
                            <td><span class="badge bg-info">${escapeHtml(item.lead_type)}</span></td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center">No duplicate details found.</td></tr>';
            }
            $('#duplicateLeadsModalBody').html(html);
            $('#duplicateLeadsModal').modal('show');
        });

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


    });
</script>