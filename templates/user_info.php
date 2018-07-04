<?php
global $name,$address,$phones,$title,$department,$email;
?>
<div>
	<table>
		<tr>
			<td> <span class="user-data-header"> Name: </span></td> <td><?= $name ?></td>
			<td><span class="user-data-header">Address: </span></td> <td><?= $address ?></td>
			<td><span class="user-data-header">Phone Numbers:</span> </td> <td><?= implode(' ; ',$phones) ?></td>
		</tr>

		<tr>
			<td><span class="user-data-header">Title: </span> </td> <td><?= $title ?></td>
			<td><span class="user-data-header">Department: </span></td> <td><?= $department ?></td>
			<td><span class="user-data-header">Email </span></td> <td><?= $email ?></td>
		</tr>
	</table>

</div>