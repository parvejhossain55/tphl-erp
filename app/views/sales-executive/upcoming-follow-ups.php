 <!-- Upcoming Follow up -->
 <div class="col-12 mt-3">
     <div class="card__wrapper">
         <div class="card__header d-flex justify-content-between align-items-center mb-3">
             <h5 class="card__title mb-0" id="transactionsTitle">
                 Upcoming Follow-ups
             </h5>

         </div>

         <div class="table__wrapper table-responsive">
             <table class="table mb-20">
                 <thead>
                     <tr class="table__title">
                         <th>S/L</th>
                         <th>Last Contact Date</th>
                         <th>Lead Name</th>
                         <th>phone</th>
                         <th>Project</th>
                         <th>Flat</th>
                         <th>Last Decussion Note</th>
                         <th>Next Action</th>
                         <th>Upcoming Follow Up Date</th>

                     </tr>
                 </thead>
                 <tbody>
                     <?php if (!empty($upcomingLeads)): ?>
                         <?php foreach ($upcomingLeads as $key => $followUp): ?>
                             <tr>
                                 <td><?= $key + 1 ?></td>
                                 <td><?= !empty($followUp['follow_up_date']) ? date('d M, Y', strtotime($followUp['follow_up_date'])) : 'N/A' ?></td>
                                 <td><?= htmlspecialchars($followUp['lead_name']) ?></td>
                                 <td><?= htmlspecialchars($followUp['phone_number'] ?? $followUp['phone']) ?></td>
                                 <td><?= htmlspecialchars($followUp['project_name']) ?></td>
                                 <td><?= htmlspecialchars($followUp['flat_name']) ?></td>
                                 <td><?= htmlspecialchars($followUp['discussion_note'] ?? 'N/A') ?></td>
                                 <td><?= htmlspecialchars($followUp['next_action'] ?? 'N/A') ?></td>
                                   <td><?= !empty($followUp['next_follow_up_date']) ? date('d M, Y', strtotime($followUp['next_follow_up_date'])) : 'N/A' ?></td>
                             </tr>
                         <?php endforeach; ?>
                     <?php else: ?>
                         <tr>
                             <td colspan="7" class="text-center py-4">
                                 <div class="text-muted">No follow-ups found</div>
                             </td>
                         </tr>
                     <?php endif; ?>
                 </tbody>

             </table>
         </div>
     </div>
 </div>