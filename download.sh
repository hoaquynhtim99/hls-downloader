#!/bin/bash

SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  TARGET="$(readlink "$SOURCE")"
  if [[ $TARGET == /* ]]; then
    SOURCE="$TARGET"
  else
    DIR="$(dirname "$SOURCE")"
    SOURCE="$DIR/$TARGET"
  fi
done
RDIR="$(dirname "$SOURCE")"
DIR="$(cd -P "$(dirname "$SOURCE")" >/dev/null 2>&1 && pwd)"

DIR_PATH=$PWD

# Kiểm tra các thư viện
if ! [ -x "$(command -v php)" ]; then
  echo -e "\033[0;31mNo PHP\033[0m"
  exit
fi
if ! [ -x "$(command -v ffmpeg)" ]; then
  echo -e "\033[0;31mNo ffmpeg. Visit https://ffmpeg.org/download.html to install before run this tool\033[0m"
  exit
fi

clean_file () {
  if [ -f "$DIR_PATH/tmp.download" ]; then
    rm -f "$DIR_PATH/tmp.download"
  fi
  if [ -f "$DIR_PATH/tmp.m3u8" ]; then
    rm -f "$DIR_PATH/tmp.m3u8"
  fi
}

clean_file

php "$DIR_PATH/download.php" $1 $2
CODE=$?
if [[ $CODE == 1 ]]; then
  # Dừng khi lỗi
  exit
fi

echo "Begin convert downloaded temp file to video"

FILENAME="$(date +%Y-%m-%d-%H-%M).mp4"

if [[ $CODE == 2 ]]; then
  # MPEG transport stream => mp4
  ffmpeg -y -f mpegts -i "$DIR_PATH/tmp.download" -c copy "$DIR_PATH/$FILENAME"
else
  echo -e "\033[0;31mDownload format cannot be recognized!\033[0m"
  clean_file
  exit
fi

clean_file

echo -e "\033[0;32mSaved to $FILENAME!\033[0m"
