#!/bin/sh

VERSION=`jq -r .version meta.json`

rm -rf dist/*

mkdir -p dist/pawcommerce-for-woocommerce
mkdir -p dist/pawcommerce-for-woocommerce/assets/images/icons

cp src/* dist/pawcommerce-for-woocommerce
cp license.txt dist/pawcommerce-for-woocommerce
cp readme.txt dist/pawcommerce-for-woocommerce
cp assets/pawc-pay-button.png dist/pawcommerce-for-woocommerce/assets/images/icons

cd dist
zip -r pawcommerce-for-woocommerce-${VERSION}.zip pawcommerce-for-woocommerce
