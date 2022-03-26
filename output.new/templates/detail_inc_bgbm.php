<table border='0'>
  <tr>
    <td style="vertical-align: top; width: 30px;">
      <a href='https://<?php echo $specimen['iiif_proxy'] . $specimen['iiif_dir']; ?>/?manifest=<?php echo $output['manifest']; ?>' target='_blank'>
        <img height='15' width='15' src='images/logo-iiif.png'>
      </a>
    </td>
    <td valign='top' align='center'>
      <?php if ($specimen['iiif_capable']): ?>
        <iframe title='Mirador' width='100%' height='800px' src='https://<?php echo $specimen['iiif_proxy'] . $specimen['iiif_dir']; ?>/?manifest=<?php echo $output['manifest']; ?>' allowfullscreen='true' webkitallowfullscreen='true' mozallowfullscreen='true'>
        </iframe>
      <?php else: ?>
        <a href='image.php?<?php echo $output['bgbm_options']; ?>&method=show' target='imgBrowser'>
          <img src='image.php<?php echo $output['bgbm_options']; ?>&method=thumb' border='2'>
        </a><br>"
        (<a href='image.php<?php echo $output['bgbm_options']; ?>&method=show'>Open viewer</a>)
      <?php endif; ?>
    </td>
  </tr>
</table>
