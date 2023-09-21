# Introduction

> Stylish off-server image transforms #craftcms projects

## What

ImageGuru provides drop-im off-server image transforms for Craft CMS that don't require template changes. By hooking in to Craft's built-in image transforms, ImageGuru allows you to use the same syntax you're used to, but with the added performance and storage benefits of off-server image transforms. 

## Why?

Craft's built-in image transforms are great, but they have a few drawbacks:

1. They run on the web server, which can be slow and resource intensive
2. They require you to store the transformed images in your Asset Volume, which can be expensive in terms of storage costs, especially if you have a lot of transforms (think `srcset`)
3. There are plenty of great image transform plugins already out there, but they generally they either require you to change your template code to use them and/or they are paid plugins. 
4. ImageGuru is FOSS and is designed to be a drop-in replacement for Craft's built-in image transforms. 
5. Designed to be easy to extend to support other image transform services.

## Supported image transform services

Currently, ImageGuru [bundles the following image transform services](./03-bundled-transformers):

- AWS Serverless Sharp Image Handler
- Cloudflare Image Resizing (Basic)
- Cloudflare Image Resizing (Worker)

You can also easily [define your own custom transformers](./04-adding-transformers) should you need to.

Planned:

- Imgix

## Get started

1. [Installation](./01-installation)
2. [Configuration](./02-config)
3. [Using a bundled Transformer](./03-bundled-transformers)
4. [Adding a custom Transformer](./04-adding-transformers)
