<div class="auth-scene">
  <div class="auth-brand"><span class="brand-mark">UB</span><div>UNIVERSITY OF BAGUIO<small>CONCERN REPORT SYSTEM</small></div></div>
  <div class="auth-story"><span class="eyebrow">YOUR VOICE. OUR CAMPUS.</span><h1>A better campus<br>starts with <em>you.</em></h1><p>Raise a concern. Stay informed. Help make our university a safer, more caring place for everyone.</p><div class="auth-pills"><span>Report with confidence</span><span>Track your concerns</span></div></div>
  <section class="login-card" aria-labelledby="signin-title"><span class="eyebrow">WELCOME TO YOUR CAMPUS PORTAL</span><h2 id="signin-title">Good to see you.</h2><p class="auth-intro">Sign in to your account to get started.</p>
      <form class="login-form" method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="login">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" value="<?= e($loginData->username) ?>" name="username" placeholder="Enter your username" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-input">
            <input type="<?= e(($showPassword ? 'text' : 'password')) ?>" id="password" value="<?= e($loginData->password) ?>" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="toggle-password" data-password="password">
              <?= e(($showPassword ? 'Hide' : 'Show')) ?>
            </button>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" <?= $isLoading ? 'disabled' : '' ?>>
            <?= e(($isLoading ? 'Logging in...' : 'Sign in →')) ?>
          </button>
        </div>

        <?php if ($errorMessage): ?><div class="error-message">
          <?= e($errorMessage) ?>
        </div><?php endif; ?>
      </form>

<div class="login-footer"><p>New to the portal? <a href="<?= e(url('register')) ?>">Create an account</a></p></div><div class="auth-note">For students, administrators &amp; campus security</div></section>
<div class="auth-bottom">University of Baguio <span>A connected community. A campus that cares.</span></div>
</div>