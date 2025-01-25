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
cd "$DIR/"

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

EXISTSPROCESS=0

# Kiểm tra tiến trình tồn tại
check_exist_progress() {
  if [ ! -d "$DIR_PATH/meta" ] || [ ! -d "$DIR_PATH/data" ]; then
    return
  fi
  checkFiles=(
    "list.m3u8"
    "thread_1.json"
    "thread_2.json"
    "thread_3.json"
    "thread_4.json"
    "ffmpeg_mode.txt"
    "segs_total.txt"
  )
  for f in ${checkFiles[@]}; do
    if [ ! -f "$DIR_PATH/meta/$f" ]; then
      return
    fi
  done
  EXISTSPROCESS=1
}
check_exist_progress

# Dọn file tạm
clean_file () {
  if [ -d "$DIR_PATH/meta" ]; then
    rm -rf "$DIR_PATH/meta"
  fi
  if [ -d "$DIR_PATH/data" ]; then
    rm -rf "$DIR_PATH/data"
  fi
}

NEWTASK=0
if [[ $EXISTSPROCESS == 1 ]]; then
  # Hỏi chạy tiếp hay dừng
  CPROCESS=1
  echo "You have an unfinished process"
  while true; do
    read -p "Make a new process or continue [y(default):continue / n:new process]? " yn
    if [[ "$yn" = "y" || "$yn" = "Y" || "$yn" = "" ]] ; then
      break
    elif [[ "$yn" = "n" || "$yn" = "N" ]] ; then
      CPROCESS=0
      break
    else
      echo "Please enter y or n or leave blank"
    fi
  done

  if [[ $CPROCESS == 0 ]]; then
    # Không tiếp tục thì xóa dữ liệu tạm
    NEWTASK=1
    clean_file
  fi
else
  # Xóa dữ liệu tạm
  NEWTASK=1
  clean_file
fi

mkdir -p "$DIR_PATH/meta"
mkdir -p "$DIR_PATH/data"

if [[ $NEWTASK == 1 ]]; then
  URL="$1"
  URL2="$2"
  if [ -z "$URL" ]; then
    while true; do
      read -p "Enter URL: " URL
      if [[ -n "$URL" ]]; then
        break
      else
        echo "No URL, again"
      fi
    done
  fi
  php "$DIR_PATH/m3u8.php" $URL $URL2
  CODE=$?
  if [[ $CODE == 1 ]]; then
    # Dừng khi lỗi
    exit
  fi
fi

# Xóa file ghi lỗi
if [ -f "$DIR_PATH/meta/error.txt" ]; then
  rm -f "$DIR_PATH/meta/error.txt"
fi

# Ghi ra timestamp bắt đầu
timestamp=$(date +%s)
echo "$timestamp" > "$DIR_PATH/meta/start.txt"
echo ""

# Tải 4 luồng cùng lúc
php "$DIR_PATH/segment.php" 1 &
php "$DIR_PATH/segment.php" 2 &
php "$DIR_PATH/segment.php" 3 &
php "$DIR_PATH/segment.php" 4

# Đợi cho 4 luồng xong hết
wait

if [ -f "$DIR_PATH/meta/error.txt" ]; then
  exit
fi

# Nối các file tải về làm 1
echo "Begin concat downloaded segments"
if [ -f "$DIR_PATH/data/downloaded" ]; then
  rm -f "$DIR_PATH/data/downloaded"
fi
cat $DIR_PATH/data/seg_* > $DIR_PATH/data/downloaded

# Xác định loại file đầu vào
ffmpeg_mode=$(cat "$DIR_PATH/meta/ffmpeg_mode.txt")

echo "Begin convert downloaded temp file to video"

FILENAME="$(date +%Y-%m-%d-%H-%M).mp4"
if [ -z "$2" ]; then
  FILENAME="${2}.mp4"
fi

if [[ "$ffmpeg_mode" == "2" ]]; then
  # MPEG transport stream => mp4
  ffmpeg -y -f mpegts -i "$DIR_PATH/data/downloaded" -c copy "$DIR_PATH/$FILENAME"
  if [[ $? == 1 ]]; then
    # Dừng khi lỗi
    exit
  fi
else
  echo -e "\033[0;31mDownload format cannot be recognized!\033[0m"
  exit
fi

clean_file

echo -e "\033[0;32mSaved to $FILENAME!\033[0m"
