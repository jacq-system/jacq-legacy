<table border='0'>
  <tr>
    <?php foreach ($output['phaidraThumbs'] as $thumb): ?>
      <td valign='top' align='center'>
        <a href="<?php echo $thumb['viewer'] ?>" target='imgBrowser'>
          <img src='<?php echo $thumb['img']; ?>/full/!200,200/0/default.jpg'>
        </a>
        <br>
         (<a href='<?php echo $thumb['img']; ?>/full/full/0/default.jpg'>JPEG</a>)
      </td>
    <?php endforeach; ?>
  </tr>
</table>
