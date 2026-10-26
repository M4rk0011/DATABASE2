<div class="report-concern-container">
  <div class="report-header">
    <h1><?= !empty($concernData->id) ? 'Edit Concern' : 'Report New Concern' ?></h1>
    <p>Fill in the details below to report a concern at University of Baguio</p>
  </div>

  <form class="concern-form" method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action" value="save-report">
    
    <div class="form-section">
      <h2>Concern Details</h2>
      
      <div class="form-group">
        <label for="title">Concern Title *</label>
        <input type="text" id="title" value="<?= e($concernData->title) ?>" name="title" placeholder="Brief description of the concern" required>
      </div>

      <div class="form-group">
        <label for="description">Detailed Description *</label>
        <textarea id="description" name="description" rows="4" placeholder="Provide a detailed description of what happened" required><?= e($concernData->description) ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="category">Category *</label>
          <select id="category" name="category" required>
            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $concernData->category === $category ? 'selected' : '' ?>>
              <?= e(strtoupper(str_replace('_', ' ', $category))) ?>
            </option><?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="severity">Severity *</label>
          <select id="severity" name="severity" required>
            <?php foreach ($severities as $severity): ?><option value="<?= e($severity) ?>" <?= $concernData->severity === $severity ? 'selected' : '' ?>>
              <?= e(strtoupper($severity)) ?>
            </option><?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    
    <div class="form-section">
      <h2>Location Information</h2>
      
      <div class="form-group">
        <label for="address">Location Address *</label>
        <input type="text" id="address" value="<?= e($concernData->location->address) ?>" name="address" placeholder="e.g., University of Baguio Main Library, 2nd Floor" required>
      </div>

      <div class="quick-locations">
        <label>Quick Locations (address only — use the map below to set the pin):</label>
        <div class="location-buttons">
          <button type="button" class="btn btn-outline" data-location="Main Library">Main Library</button>
          <button type="button" class="btn btn-outline" data-location="Gymnasium">Gymnasium</button>
          <button type="button" class="btn btn-outline" data-location="Parking Lot">Parking Lot</button>
          <button type="button" class="btn btn-outline" data-location="Administration">Administration</button>
          <button type="button" class="btn btn-outline" data-location="Cafeteria">Cafeteria</button>
        </div>
      </div>

      <div class="map-section">
        <label class="map-label">Pin location on campus map *</label>
        <p class="map-hint">The red outline is the campus reporting area (around 16°24′56″N 120°35′51″E). Click the map or drag the pin to set the exact location; coordinates are saved with your report.</p>
        <div id="report-map" class="report-leaflet-map" role="application" aria-label="Campus map to select concern location"></div>
        <p class="coordinates-readout">
          Latitude <?= e(number_format((float)$concernData->location->latitude, 5)) ?>,
          longitude <?= e(number_format((float)$concernData->location->longitude, 5)) ?>
        </p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="building">Building</label>
          <input type="text" id="building" value="<?= e($concernData->location->building) ?>" name="building" placeholder="e.g., Main Library">
        </div>

        <div class="form-group">
          <label for="floor">Floor</label>
          <input type="text" id="floor" value="<?= e($concernData->location->floor) ?>" name="floor" placeholder="e.g., 2nd Floor">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="room">Room/Area</label>
          <input type="text" id="room" value="<?= e($concernData->location->room) ?>" name="room" placeholder="e.g., Study Area A">
        </div>

        <div class="form-group">
          <label for="landmark">Nearby Landmark</label>
          <input type="text" id="landmark" value="<?= e($concernData->location->landmark) ?>" name="landmark" placeholder="e.g., Near entrance gate">
        </div>
      </div>

    </div>

    
    <div class="form-section">
      <h2>Contact Information</h2>
      
      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" <?= $concernData->isAnonymous ? 'checked' : '' ?> name="isAnonymous">
          Report anonymously
        </label>
      </div>

      <div id="contact-fields">
        <div class="form-row">
          <div class="form-group">
            <label for="contactName">Your Name *</label>
            <input type="text" id="contactName" value="<?= e($concernData->contactInfo->name) ?>" name="contactName" placeholder="Your full name" required>
          </div>

          <div class="form-group">
            <label for="contactEmail">Email Address *</label>
            <input type="email" id="contactEmail" value="<?= e($concernData->contactInfo->email) ?>" name="contactEmail" placeholder="your.email@ub.edu.ph" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="contactPhone">Phone Number</label>
            <input type="tel" id="contactPhone" value="<?= e($concernData->contactInfo->phone) ?>" name="contactPhone" placeholder="+63-XXX-XXX-XXXX">
          </div>

          <div class="form-group">
            <label for="studentId">Student ID</label>
            <input type="text" id="studentId" value="<?= e($concernData->contactInfo->studentId) ?>" name="studentId" placeholder="e.g., 2024-12345">
          </div>
        </div>

        <div class="form-group">
          <label for="department">Department</label>
          <input type="text" id="department" value="<?= e($concernData->contactInfo->department) ?>" name="department" placeholder="e.g., College of Engineering">
        </div>
      </div>
    </div>

    
    <div class="form-section">
      <h2>Witnesses</h2>
      
      <div class="witness-input-group">
        <input type="text" value="<?= e($witnessInput) ?>" name="witnessInput" placeholder="Enter witness name" data-witness-input="true">
        <button type="button" class="btn btn-outline" data-witness-add="true">Add Witness</button>
      </div>

      <div class="witnesses-list" id="witnesses-list">
        <?php foreach ($concernData->witnesses as $i => $witness): ?><div class="witness-item">
          <span><?= e($witness) ?></span>
          <button type="button" class="btn-remove" data-witness-remove="true">×</button>
        <input type="hidden" name="witnesses[]" value="<?= e($witness) ?>"></div><?php endforeach; ?>
      </div>
    </div>

    
    <div class="form-section">
      <h2>Photos</h2>
      <label for="photos">Attach photos of the concern</label>
      <p id="photo-help">JPG, PNG or WebP. Maximum 5 photos per report, 2 MB each. Select multiple photos together. After an error, select your new photos again.</p>
      <input id="photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="photo-help">
      <p id="photo-error" role="alert"></p><div id="photo-preview" class="photo-gallery"></div>
      <div class="photo-gallery">
      <?php foreach (($concernData->images ?? []) as $photo): ?>
        <label class="photo-card"><img src="<?= e(photoUrl($concernData->id, $photo)) ?>" alt="Attached concern photo" loading="lazy">
        <span><input type="checkbox" name="removePhotos[]" value="<?= e($photo) ?>"> Remove photo</span></label>
      <?php endforeach; ?>
      </div>
    </div>

    <div class="form-section">
      <h2>Additional Information</h2>
      
      <div class="form-group">
        <label for="additionalNotes">Additional Notes</label>
        <textarea id="additionalNotes" name="additionalNotes" rows="3" placeholder="Any additional information that might be helpful"><?= e($concernData->additionalNotes) ?></textarea>
      </div>
    </div>

    
    <div class="form-actions">
      <?php if ($errorMessage): ?><div class="error-message">
        <?= e($errorMessage) ?>
      </div><?php endif; ?>

      <?php if ($successMessage): ?><div class="success-message">
        <?= e($successMessage) ?>
      </div><?php endif; ?>

      <div class="action-buttons">
        <button type="button" class="btn btn-outline" data-href="<?= e(url('dashboard')) ?>">Cancel</button>
        <button type="submit" class="btn btn-primary" <?= $isSubmitting ? 'disabled' : '' ?>>
          <?= e((!empty($concernData->id) ? 'Save Changes' : 'Submit Report')) ?>
        </button>
      </div>
    </div>
  <input type="hidden" name="id" value="<?= e($concernData->id ?? '') ?>"><input type="hidden" name="latitude" value="<?= e($concernData->location->latitude) ?>"><input type="hidden" name="longitude" value="<?= e($concernData->location->longitude) ?>"></form>
</div>
