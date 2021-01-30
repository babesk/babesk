<html>

<h3>Zeitraster für den Elternsprechtag am {$category['name']}</h3>
{*Try to extract the room from the meetings. Should be ok since the room is
 *the same for a host and category
 *}
{$room = $meetings.0.roomName}

<table>
	<tbody>
		<tr>
			<td>
				<table border="1" cellpadding="10">
					<tbody>
						<tr>
							<td colspan="2">
								Lehrkraft: {$host['forename']} {$host['name']}
							</td>
						</tr>
						{*First 20 Meetings go on the left-handed Column*}
						{$firstColMeetings = array_slice($meetings, 0, 20)}
						{foreach $firstColMeetings as $meeting}
							<tr>
								<td width="55"
									{if $meeting.isDisabled}style="background-color: grey"{/if}
								>
									<b>{substr_replace($meeting.meetingTime, '', 5)}</b>
								</td>
								<td width="260"
									{if $meeting.isDisabled}style="background-color: grey"{/if}
								>
									{$meeting.userForename} {$meeting.userName}
								</td>
							</tr>
							{$meetingsDisplayedCounter = $meetingsDisplayedCounter + 1}
						{/foreach}
					</tbody>
				</table>
			</td>
			<td>
				<table border="1" cellpadding="10">
					<tbody>
						<tr>
							<td colspan="2">
							Raum: {$room}
							</td>
						</tr>
						{*The others go on the right side*}
						{$secondColMeetings = array_slice($meetings, 20)}
						{foreach $secondColMeetings as $meeting}
							<tr>
								<td width="55"
									{if $meeting.isDisabled}style="background-color: grey"{/if}
								>
								<b>{substr_replace($meeting.meetingTime, '', 5)}</b>
								</td>
								<td width="260"
									{if $meeting.isDisabled}style="background-color: grey"{/if}
								>
									{$meeting.userForename} {$meeting.userName}
								</td>
							</tr>
							{$meetingsDisplayedCounter = $meetingsDisplayedCounter + 1}
						{/foreach}
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>

</html>