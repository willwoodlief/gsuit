<?php
global $not_in_users,$not_in_spreadsheet;
?>
<div class="row"  style="margin: 5em">
    <h1 style="text-decoration: underline">Page for finding missing entries</h1>
</div>
<div class="row"  style="margin: 5em">
<?php if (empty($not_in_users)) { ?>
	<h2>There is no entries in the spreadsheet that are not primary addresses of users of the GSuit</h2>
<?php } else {?>
    <h2> These are the primary addresses that are in the spreadsheet but missing in GSuit</h2>
	<?php foreach ($not_in_users as $email) { ?>

		<div class='col-sm-12 col-md-6 col-lg-4 '>
			<span class="missing-email"><?= $email['email'] ?></span>
		</div>
	<?php } ?>
<?php } ?>
</div>
<div class="row" style="margin: 5em">
<?php if (empty($not_in_spreadsheet)) { ?>
	<h2>There is no primary addresses of users of the GSuit that are not in the spreadsheet</h2>
<?php } else {?>
    <h2> These are the primary addresses that  are missing in the spreadsheet</h2>
	<?php foreach ($not_in_spreadsheet as $email) { ?>

	<div class='col-sm-12 col-md-6 col-lg-4 '>
		<span class="missing-email"><?= $email->emails[0]['address'] ?></span>
	</div>
	<?php } ?>
<?php } ?>
</div>
