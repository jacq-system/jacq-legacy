<table border='0'>
    <tr>
        <td style="vertical-align: top; width: 30px;">
            <a href='<?php echo $specimen['iiif_url']; ?>?manifest=<?php echo $output['manifest']; ?>' target='_blank'>
                <img height='15' width='15' src='images/logo-iiif.png'>
            </a>
        </td>
        <td valign='top' align='center'>
            <iframe title='Mirador' width='100%' height='800px' src='<?php echo $specimen['iiif_url']; ?>?manifest=<?php echo $output['manifest']; ?>' allowfullscreen='true' webkitallowfullscreen='true' mozallowfullscreen='true'>
            </iframe>
        </td>
    </tr>
</table>
