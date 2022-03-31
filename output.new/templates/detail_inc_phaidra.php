<table border='0'>
  <tr>
    <td style="vertical-align: top; width: 30px;">
      <a href='<?php echo $thumb['viewer'] ?>' target='_blank'><img height='15' width='15' src='images/logo-iiif.png'></a>
    </td>
    <?php foreach ($output['phaidraThumbs'] as $thumb): ?>
      <td valign='top' align='center'>
        <a href="<?php echo $thumb['viewer'] ?>" target='imgBrowser'>
          <img src='<?php echo $thumb['img']; ?>/full/!200,200/0/default.jpg'>
        </a>
        <br>
        (<a href='downloadPhaidra.php?filename=<?php echo $thumb['file']; ?>.jpg&url=<?php echo $thumb['img']; ?>/full/full/0/default.jpg'>JPEG</a>)
      </td>
    <?php endforeach; ?>
  </tr>
</table>
