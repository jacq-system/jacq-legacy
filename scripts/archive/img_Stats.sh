#!/bin/bash

echo "Total images in /data/../specimens/web-dir "
find /data/images/specimens/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /data/../specimens/web-dir"
find /data/images/specimens/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;
echo ""
echo "--"
echo ""
echo "Total images in /data/../specimens/originale-dir "
find /data/images/specimens/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /data/../specimens/originale-dir "
find /data/images/specimens/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;

echo ""
echo "************************"
echo ""
echo "Total images in /herbed01/web-dir "
find /data/images/specimens/herbed01/specimens/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /herbed01/web-dir "
find /data/images/specimens/herbed01/specimens/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;
echo ""
echo "--"
echo ""
echo "Total images in /herbed01/originale-dir "
find /data/images/specimens/herbed01/specimens/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /herbed01/originale-dir "
find /data/images/specimens/herbed01/specimens/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;

echo ""
echo "************************"
echo ""
echo "Total images in /msa_20/web-dir "
find /data/images/specimens/msa20/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /msa_20/web-dir "
find /data/images/specimens/msa20/web/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;
echo ""
echo "--"
echo ""
echo "Total images in /msa_20/originale-dir"
find /data/images/specimens/msa20/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f | wc -l ' \;
echo "------------------------"
echo "Non-Unique images in /msa_20/originale-dir "
find /data/images/specimens/msa20/originale/ -maxdepth 1 -type d -exec echo -n "{}: " \; -exec bash -c 'find {} -type f -name "*_*_*.*" | wc -l ' \;

