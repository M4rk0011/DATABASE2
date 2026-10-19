<div class="concern-map-container">
  <div class="map-header">
    <h2>University of Baguio Concern Map</h2>
    <p>Select a concern or tap a severity-colored pin on the map. The red outline matches the concern report map and shows where reports may be placed.</p>
  </div>

  <div class="map-layout">
    
    <div class="map-section">
      <div id="map" class="google-map"></div>
      <?php if (!($mapInitialized)): ?><div class="map-loading">
        <div class="loading-spinner"></div>
        <p>Loading University of Baguio Campus Map...</p>
      </div><?php endif; ?>
      
    </div>

    
    <?php if ($selectedConcern): ?><div class="details-panel">
      <div class="panel-header">
        <h3><?= e($selectedConcern->title) ?></h3>
        <button class="close-btn" data-href="<?= e(url('map')) ?>">×</button>
      </div>
      
      <div class="panel-content">
        <div class="concern-meta">
          <span class="severity-badge" style="background-color:<?= e(getSeverityColor($selectedConcern->severity)) ?>">
            <?= e(strtoupper($selectedConcern->severity)) ?>
          </span>
          <span class="status-badge" style="background-color:<?= e(getStatusColor($selectedConcern->status)) ?>">
            <?= e(strtoupper(str_replace('_', ' ', $selectedConcern->status))) ?>
          </span>
        </div>

        <div class="concern-description">
          <p><?= e($selectedConcern->description) ?></p>
        </div>

        <div class="concern-details">
          <div class="detail-row">
            <strong>Category:</strong> <?= e(strtoupper(str_replace('_', ' ', $selectedConcern->category))) ?>
          </div>
          <div class="detail-row">
            <strong>Location:</strong> <?= e($selectedConcern->location->address) ?>
          </div>
          <?php if ($selectedConcern->location->building): ?><div class="detail-row">
            <strong>Building:</strong> <?= e($selectedConcern->location->building) ?>
          </div><?php endif; ?>
          <?php if ($selectedConcern->location->floor): ?><div class="detail-row">
            <strong>Floor:</strong> <?= e($selectedConcern->location->floor) ?>
          </div><?php endif; ?>
          <?php if ($selectedConcern->location->room): ?><div class="detail-row">
            <strong>Room:</strong> <?= e($selectedConcern->location->room) ?>
          </div><?php endif; ?>
          <div class="detail-row">
            <strong>Reported:</strong> <?= e(fmtdate($selectedConcern->reportedAt)) ?>
          </div>
          <div class="detail-row">
            <strong>Updated:</strong> <?= e(fmtdate($selectedConcern->updatedAt)) ?>
          </div>
          <div class="detail-row">
            <strong>Assigned to:</strong> <?= e(($selectedConcern->assignedTo ?: 'Unassigned')) ?>
          </div>
        </div>

        <?php if ((!($selectedConcern->isAnonymous) || $isAdmin)): ?><div class="contact-info">
          <h4>Contact Information</h4>
          <div class="contact-details">
            <div class="contact-row">
              <strong>Name:</strong> <?= e($selectedConcern->contactInfo->name) ?>
            </div>
            <div class="contact-row">
              <strong>Email:</strong> <?= e($selectedConcern->contactInfo->email) ?>
            </div>
            <?php if ($selectedConcern->contactInfo->phone): ?><div class="contact-row">
              <strong>Phone:</strong> <?= e($selectedConcern->contactInfo->phone) ?>
            </div><?php endif; ?>
            <?php if ($selectedConcern->contactInfo->studentId): ?><div class="contact-row">
              <strong>Student ID:</strong> <?= e($selectedConcern->contactInfo->studentId) ?>
            </div><?php endif; ?>
            <?php if ($selectedConcern->contactInfo->department): ?><div class="contact-row">
              <strong>Department:</strong> <?= e($selectedConcern->contactInfo->department) ?>
            </div><?php endif; ?>
          </div>
        </div><?php endif; ?>

        <?php if ((count($selectedConcern->witnesses) > 0)): ?><div class="witnesses-info">
          <h4>Witnesses</h4>
          <ul>
            <?php foreach ($selectedConcern->witnesses as $witness): ?><li><?= e($witness) ?></li><?php endforeach; ?>
          </ul>
        </div><?php endif; ?>

        <?php if ($selectedConcern->additionalNotes): ?><div class="additional-notes">
          <h4>Additional Notes</h4>
          <p><?= e($selectedConcern->additionalNotes) ?></p>
        </div><?php endif; ?>

        <?php if (canManageConcerns()): ?><div class="concern-actions">
          <button type="button" class="btn btn-primary" data-href="<?= e(url('report', ['id' => $selectedConcern->id])) ?>">Edit Concern</button>
          <button type="button" class="btn btn-info" data-toggle="status-picker">
            <?= e(($statusPickerOpen ? 'Hide status options' : 'Update Status')) ?>
          </button>
          <div class="action-subpanel" id="status-picker" hidden>
            <?php foreach ($concernStatusOptions as $s): ?><button type="button" class="btn btn-outline" data-action="status" data-id="<?= e($selectedConcern->id) ?>" data-value="<?= e($s) ?>">
              Set to <?= e(strtoupper((string)formatStatusLabel($s))) ?>
            </button><?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-success" data-toggle="assign-picker">
            <?= e(($assignPickerOpen ? 'Hide assign options' : 'Assign to Staff')) ?>
          </button>
          <div class="action-subpanel" id="assign-picker" hidden>
            <button type="button" class="btn btn-outline" data-action="assign" data-id="<?= e($selectedConcern->id) ?>" data-value="security">Security team</button>
            <button type="button" class="btn btn-outline" data-action="assign" data-id="<?= e($selectedConcern->id) ?>" data-value="admin">Administration</button>
            <button type="button" class="btn btn-outline" data-action="assign" data-id="<?= e($selectedConcern->id) ?>" data-value="facilities">Facilities</button>
            <button type="button" class="btn btn-outline" data-action="assign" data-id="<?= e($selectedConcern->id) ?>" data-value="medical">Medical / clinic</button>
          </div>
        </div><?php endif; ?>
      </div>
    </div><?php endif; ?>

    
    <?php if (!($selectedConcern)): ?><div class="stats-panel">
      <h3>Concern Statistics</h3>
      <div class="stats-grid">
        <div class="stat-item">
          <span class="stat-number"><?= e(count($markers)) ?></span>
          <span class="stat-label">Total Concerns</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= e(getNewReportsCount()) ?></span>
          <span class="stat-label">New Reports</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= e(getCriticalCount()) ?></span>
          <span class="stat-label">Critical</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= e(getResolvedCount()) ?></span>
          <span class="stat-label">Resolved</span>
        </div>
      </div>

      <div class="recent-concerns">
        <h4>Recent Concerns</h4>
        <div class="concern-list">
          <?php foreach (array_slice($markers, 0, 5) as $marker): ?><div class="concern-item" data-href="<?= e(url('map', ['id' => $marker->concern->id])) ?>">
            <div class="concern-summary">
              <span class="concern-title"><?= e($marker->concern->title) ?></span>
              <span class="concern-time"><?= e(fmtdate($marker->concern->reportedAt)) ?></span>
            </div>
            <span class="concern-severity" style="background-color:<?= e(getSeverityColor($marker->concern->severity)) ?>">
              <?= e(getSeverityInitial($marker->concern->severity)) ?>
            </span>
          </div><?php endforeach; ?>
        </div>
      </div>
    </div><?php endif; ?>
  </div>
</div>
