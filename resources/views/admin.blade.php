<!DOCTYPE html>
<html lang="vi-VN">

<head>
    <link rel="stylesheet" href="/assets/admin/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/umi.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/custom.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/i18n/runtime.css?v={{$version}}-i18n4">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            theme: {
                sidebar: '{{$theme_sidebar}}',
                header: '{{$theme_header}}',
                color: '{{$theme_color}}',
            },
            version: '{{$version}}',
            background_url: '{{$background_url}}',
            logo: '{{$logo}}',
            secure_path: '{{$secure_path}}'
        }
    </script>
    <script src="/assets/admin/i18n/catalog.js?v={{$version}}-i18n4"></script>
    <script src="/assets/admin/i18n/zh-CN.js?v={{$version}}-i18n4"></script>
    <script src="/assets/admin/i18n/vi-VN.js?v={{$version}}-i18n4"></script>
    <script src="/assets/admin/i18n/en-US.js?v={{$version}}-i18n4"></script>
    <script src="/assets/admin/i18n/ru-RU.js?v={{$version}}-i18n4"></script>
    <script src="/assets/admin/i18n/runtime.js?v={{$version}}-i18n4"></script>
</head>

<body>
<div id="root"></div>
<script src="/assets/admin/vendors.async.js?v={{$version}}"></script>
<script src="/assets/admin/components.async.js?v={{$version}}"></script>
<script src="/assets/admin/umi.js?v={{$version}}-i18n4"></script>
</body>

</html>
