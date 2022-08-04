<?php
if ($output['djatoka']['error']) {
    echo $output['djatoka']['error'];
}
if (!empty($output['djatoka_options'])):
?>
  <table border='0'>
    <tr>
      <?php foreach ($output['djatoka_options'] as $option): ?>
        <td valign='top' align='center'>
          <a href='image.php?<?php echo $option; ?>&method=show' target='imgBrowser'>
            <img src='image.php?<?php echo $option; ?>&method=thumb' style='border: 2px;'>
          </a>
          <br>
          (<a href='image.php?<?php echo $option; ?>&method=download&format=jpeg2000'>JPEG2000</a>,
           <a href='image.php?<?php echo $option; ?>&method=download&format=tiff'>TIFF</a>)
        </td>
      <?php endforeach; ?>
    </tr>
  </table>
<?php endif; ?>
<?php
if (!empty($output['djatoka_transfer_output'])) {
    echo nl2br($output['djatoka_transfer_output'], true);
}
?>
