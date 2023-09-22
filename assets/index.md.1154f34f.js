import{_ as e,o as a,c as r,Q as t}from"./chunks/framework.19f61efe.js";const p=JSON.parse('{"title":"Introduction","description":"","frontmatter":{},"headers":[],"relativePath":"index.md","filePath":"index.md"}'),o={name:"index.md"},i=t('<h1 id="introduction" tabindex="-1">Introduction <a class="header-anchor" href="#introduction" aria-label="Permalink to &quot;Introduction&quot;">​</a></h1><blockquote><p>Stylish off-server image transforms #craftcms projects</p></blockquote><h2 id="what" tabindex="-1">What <a class="header-anchor" href="#what" aria-label="Permalink to &quot;What&quot;">​</a></h2><p>ImageGuru provides drop-im off-server image transforms for Craft CMS that don&#39;t require template changes. By hooking in to Craft&#39;s built-in image transforms, ImageGuru allows you to use the same syntax you&#39;re used to, but with the added performance and storage benefits of off-server image transforms.</p><h2 id="why" tabindex="-1">Why? <a class="header-anchor" href="#why" aria-label="Permalink to &quot;Why?&quot;">​</a></h2><p>Craft&#39;s built-in image transforms are great, but they have a few drawbacks:</p><ol><li>They run on the web server, which can be slow and resource intensive</li><li>They require you to store the transformed images in your Asset Volume, which can be expensive in terms of storage costs, especially if you have a lot of transforms (think <code>srcset</code>)</li><li>There are plenty of great image transform plugins already out there, but they generally they either require you to change your template code to use them and/or they are paid plugins.</li><li>ImageGuru is FOSS and is designed to be a drop-in replacement for Craft&#39;s built-in image transforms.</li><li>Designed to be easy to extend to support other image transform services.</li></ol><h2 id="supported-image-transform-services" tabindex="-1">Supported image transform services <a class="header-anchor" href="#supported-image-transform-services" aria-label="Permalink to &quot;Supported image transform services&quot;">​</a></h2><p>Currently, ImageGuru <a href="./03-bundled-transformers.html">bundles the following image transform services</a>:</p><ul><li>AWS Serverless Sharp Image Handler</li><li>Cloudflare Image Resizing (Basic)</li><li>Cloudflare Image Resizing (Worker)</li></ul><p>You can also easily <a href="./04-adding-transformers.html">define your own custom transformers</a> should you need to.</p><p>Planned:</p><ul><li>Imgix</li></ul><h2 id="get-started" tabindex="-1">Get started <a class="header-anchor" href="#get-started" aria-label="Permalink to &quot;Get started&quot;">​</a></h2><ol><li><a href="./01-installation.html">Installation</a></li><li><a href="./02-config.html">Configuration</a></li><li><a href="./03-bundled-transformers.html">Using a bundled Transformer</a></li><li><a href="./04-adding-transformers.html">Adding a custom Transformer</a></li></ol>',15),s=[i];function n(l,d,h,u,m,f){return a(),r("div",null,s)}const g=e(o,[["render",n]]);export{p as __pageData,g as default};