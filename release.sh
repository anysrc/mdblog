#!/bin/bash

cd "$(dirname "$0")"

# Version String
NOW=$(date +"%Y.%m.%d")
DATE=$NOW
BUILD=1

if [ -f "version.properties" ]; then
   DATE=$(cat "version.properties" | grep "DATE=" | cut -d '=' -f2)
   BUILD=$(cat "version.properties" | grep "BUILD=" | cut -d '=' -f2)
   BUILD=$(($BUILD+1))
fi

if [ ! "$DATE" == "$NOW" ]; then
   DATE=$NOW
   BUILD=1
fi

echo "DATE=$DATE" > "version.properties"
echo "BUILD=$BUILD" >> "version.properties"
VERSION="${DATE}.${BUILD}"
VERSIONNAME="anysrc-blog-${VERSION}"

# Deal with target folder
TARGETFOLDER="$1/$VERSIONNAME"
if [ ! -d "$TARGETFOLDER" ]; then
   mkdir -p "$TARGETFOLDER"
fi

if [ ! -d "$TARGETFOLDER" ]; then
   echo "Target dir not found.";
   exit 1;
fi

# Display configuration
echo "Create Version $VERSION"
echo "Targetfolder: $TARGETFOLDER"
echo "Releasename: $VERSIONNAME"
echo

# System files
echo "Copy..."
cp --preserve=links -R "lib/" "$TARGETFOLDER"
cp --preserve=links -R "vendor/" "$TARGETFOLDER"
cp --preserve=links -R "view/" "$TARGETFOLDER"
cp --preserve=links -R "www/" "$TARGETFOLDER"
cp --preserve=links -R ".htaccess" "$TARGETFOLDER"
cp --preserve=links -R "cmd.php" "$TARGETFOLDER"
cp --preserve=links -R "index.php" "$TARGETFOLDER"
cp --preserve=links -R "version.properties" "$TARGETFOLDER"

# Config
echo "Create configs..."
mkdir "$TARGETFOLDER/config/"
cp config/global.yml.skel "$TARGETFOLDER/config/global.yml"
cp config/user.yml.skel "$TARGETFOLDER/config/user.yml"

# Content boxes
mkdir "$TARGETFOLDER/content/"
cp "content/menubefore.html.twig.skel" "$TARGETFOLDER/content/menubefore.html.twig"
cp "content/menuafter.html.twig.skel" "$TARGETFOLDER/content/menuafter.html.twig"

# Posts
mkdir "$TARGETFOLDER/post/"
cp "post/helloworld.md.skel" "$TARGETFOLDER/post/helloworld.md"
cp post/folder.yml.skel "$TARGETFOLDER/post/folder.yml"

# Upload
echo "Create upload folder..."
mkdir "$TARGETFOLDER/upload"

# Plugin
echo "Create plugin folder..."
mkdir "$TARGETFOLDER/plugin"

# Create archive
echo "Create Archive..."
cd "$TARGETFOLDER"
cd ..
tar cvzf "${VERSIONNAME}.tar.gz" "$(basename "$TARGETFOLDER")"
rm -rf "$TARGETFOLDER"

echo

echo "done."
echo
echo "Target folder: $(pwd)"
echo "Archive: ${VERSIONNAME}.tar.gz"
echo
