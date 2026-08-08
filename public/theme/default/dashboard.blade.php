<!DOCTYPE html>
<html lang="vi-VN">

<head>
    <link rel="stylesheet" href="/theme/{{$theme}}/assets/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/theme/{{$theme}}/assets/umi.css?v={{$version}}">
    @if (file_exists(public_path("/theme/{$theme}/assets/custom.css")))
        <link rel="stylesheet" href="/theme/{{$theme}}/assets/custom.css?v={{$version}}">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    @php ($colors = [
        'darkblue' => '#3b5998',
        'black' => '#343a40',
        'default' => '#0665d0',
        'green' => '#319795'
    ])
    <meta name="theme-color" content="{{$colors[$theme_config['theme_color']]}}">

    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            assets_path: '/theme/{{$theme}}/assets',
            theme: {
                sidebar: '{{$theme_config['theme_sidebar']}}',
                header: '{{$theme_config['theme_header']}}',
                color: '{{$theme_config['theme_color']}}',
            },
            version: '{{$version}}',
            background_url: '{{$theme_config['background_url']}}',
            description: '{{$description}}',
            i18n: [
                'vi-VN',
                'ru-RU',
                'en-US',
                'zh-CN'
            ],
            logo: '{{$logo}}'
        }
    </script>
    <script src="/theme/{{$theme}}/assets/i18n/vi-VN.js?v={{$version}}-locales-2"></script>
    <script src="/theme/{{$theme}}/assets/i18n/ru-RU.js?v={{$version}}-locales-2"></script>
    <script src="/theme/{{$theme}}/assets/i18n/en-US.js?v={{$version}}-locales-2"></script>
    <script src="/theme/{{$theme}}/assets/i18n/zh-CN.js?v={{$version}}-locales-2"></script>
</head>

<body>
<div id="root"></div>
{!! $theme_config['custom_html'] !!}
<script src="/theme/{{$theme}}/assets/vendors.async.js?v={{$version}}"></script>
<script src="/theme/{{$theme}}/assets/components.async.js?v={{$version}}"></script>
<script src="/theme/{{$theme}}/assets/umi.js?v={{$version}}-locales-2"></script>
@if (file_exists(public_path("/theme/{$theme}/assets/custom.js")))
    <script src="/theme/{{$theme}}/assets/custom.js?v={{$version}}"></script>
@endif
</body>

</html>
