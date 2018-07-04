
<?php
$possible_class_for_footer = '';
if ($b_new) {
	$possible_class_for_footer = 'new-email-address';
	$btn_class = "btn-warning";
	$side_class_for_footer = "alert-side";
} else {
    $btn_class = "btn-primary";
	$side_class_for_footer = "side";
}
?>
<div class="row entry">
    <table class="edit-table">
        <tr>
            <td class="side">
                <span class="edit-start">Inbox</span>
            </td>
            <td>
                <span class="edit-email"><?= $primary_email ?></span>
            </td>
        </tr>
        <?php if ($footer === $old_sig) { ?>
            <tr>
                <td class="side">
                    <span class="edit-header">Note</span>
                </td>
                <td>
                    <span class="edit-note">The old footer and the new footer are identical</span>
                </td>
            </tr>
        <?php  }?>
        <tr>
            <td  class="side">
                <span class="edit-header">User Info</span>
            </td>
            <td>
	            <?= $user_info ?>
            </td>
        </tr>

        <tr class=" <?= $possible_class_for_footer ?> " >
            <td  class="<?= $side_class_for_footer ?>">
                <span class="edit-header">New Footer / Old Footer </span>
            </td>
            <td>
                <table>
                    <tr>
                        <td><?= $footer ?></td>
                        <td><?= $old_sig ?></td>
                    </tr>
                </table>

            </td>
        </tr>
        
        <tr>
            <td colspan="2">
                <?php if ($b_valid) { ?>
                <button class="btn <?= $btn_class ?>  btn-block update-sig"
                        data-email="<?= $primary_email ?>"
                        data-footer="<?= base64_encode($footer) ?>"
                        data-alias="<?= $send_as_email ?>"
                >
                    Update <?= $primary_email ?>
                </button>
                <br>
                    <span class="status" style="font-size: larger;color:green"></span>
                    <span class="error" style="font-size: larger;color:red"></span>
                <?php }  else {?>
                    <span class="error" style="font-size: larger;color:red">User (found in spreadsheet) is not exiting in GSuit, cannot update</span>
                <?php } ?>

            </td>
        </tr>
    </table>

</div>