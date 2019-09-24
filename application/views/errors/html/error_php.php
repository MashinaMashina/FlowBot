<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<?php if (IS_FLOWBOT): ?>
	Severity: <?php echo $severity; ?>;
	Message:  <?php echo $message; ?>;
	Filename: <?php echo $filepath; ?>;
	Line Number: <?php echo $line; ?>;
	<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>
		Backtrace:
		<?php foreach (debug_backtrace() as $error): ?>
			<?php if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>
					File: <?php echo $error['file'] ?>
					Line: <?php echo $error['line'] ?>
					Function: <?php echo $error['function'] ?>

			<?php endif ?>
		<?php endforeach ?>
	<?php endif ?>

<?php else: ?>

	<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">

	<h4>A PHP Error was encountered</h4>

	<p>Severity: <?php echo $severity; ?></p>
	<p>Message:  <?php echo $message; ?></p>
	<p>Filename: <?php echo $filepath; ?></p>
	<p>Line Number: <?php echo $line; ?></p>

	<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>

		<p>Backtrace:</p>
		<?php foreach (debug_backtrace() as $error): ?>

			<?php if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>

				<p style="margin-left:10px">
				File: <?php echo $error['file'] ?><br />
				Line: <?php echo $error['line'] ?><br />
				Function: <?php echo $error['function'] ?>
				</p>

			<?php endif ?>

		<?php endforeach ?>

	<?php endif ?>

	</div>

<?php endif; ?>