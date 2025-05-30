<!DOCTYPE html>
<html lang="en">
<head>
    <title>Audit Routes report</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            --color-text: rgb(0, 0, 0);
            --color-background: rgb(237 241 241);
            --color-border: rgba(0, 0, 0, 0.1);
            --color-primary: rgb(43, 2, 110);
            background-color: rgb(255, 255, 255);
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        * {
            box-sizing: border-box;
        }
        .content {
            flex: 1;
            background: #fff;
            border: none;
        }
        .nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 0 solid var(--color-border);
            border-bottom-width: 1px;
         }
        .nav > a,
        .nav > a:visited {
            display: inline-block;
            padding: 0.2rem;
            color: var(--color-text);
            text-decoration: none;
        }
        .nav > a:hover,
        .nav > a:active {
            color: var(--color-primary);
        }


        @media only screen and (min-width: 48rem) {
            body {
                flex-direction: row;
            }
            .nav {
                padding: 4rem 0.5rem;
                width: 16rem;
                background: var(--color-background);
                border-width: 0 1px 0 0;
            }
        }
    </style>
</head>
<body>
    <nav class="nav">
        @foreach($reports as $report => $filename)
            <a href="{{ $filename }}" target="content">
                {{ $report }}
            </a>
        @endforeach
    </nav>
    <iframe name="content" class="content" src="{{ array_shift($reports) }}"></iframe>
</body>
</html>