<!DOCTYPE html>
<html lang="en">
<head></head>
    <title>Audit Routes report</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            --color-card: rgb(255, 255, 255);
            --color-error: rgb(133, 9, 9);
            --color-primary: rgb(43, 2, 110);
            --color-success: rgb(9, 133, 13);
            --color-border: rgba(0, 0, 0, 0.1);
            --color-background: rgb(237 241 241);
            --color-background-gray: rgba(0, 0, 0, 0.1);
            background-color: rgb(255, 255, 255);
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        main {
            margin: 0 auto;
            max-width: 90rem;
            padding: 1rem;
            background-color: var(--color-background);
        }

        .content {
            margin: 0 1.5rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 500;
        }

        h2 {
            font-size: 1.2rem;
            font-weight: 200;
        }

        h3 {
            font-size: 2.5rem;
            font-weight: 100;
            margin: 0;
        }

        .text-light {
            opacity: 0.6;
        }

        .text-lighter {
            opacity: 0.4;
        }

        sub {
            font-size: 0.9rem;
            vertical-align: unset;
        }

        #aggregates {
            display: flex;
            gap: 0.4rem;
            flex-direction: column;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        #aggregates > * {
            background-color: var(--color-card);
            border-radius: 0.4rem;
            padding: 1.5rem;
            width: 100%;
        }

        #aggregates > .group {
            grid-column: 1 / 5;
            text-align: center;
            border: none;
        }

        #aggregates > .group > div {
            align-items: end;
            display: grid;
            gap: 0.2rem;
            grid-auto-flow: column;
            height: 16rem;
            margin: 0 -1rem 1rem;
        }

        #aggregates > .group > div > section {
            align-items: end;
            background: var(--color-primary);
            border-radius: 0.4rem;
            border-top: 0.1rem solid var(--color-border);
            display: flex;
            height: calc(var(--value) / var(--total) * 100%);
            min-height: 0.1rem;
            padding: 0.3rem;
            position: relative;
        }

        #aggregates > .group > div > section > span {
            display: block;
            font-size: 0.9rem;
            font-style: italic;
            opacity: 0.7;
            overflow: hidden;
            position: absolute;
            text-overflow: ellipsis;
            transform: translateY(2rem);
            white-space: nowrap;
            width: calc(95%);
        }

        #routes > * {
            background-color: var(--color-card);
            border-radius: 0.4rem;
            border-style: solid;
            border-width: 0 1rem;
            display: flex;
            flex-direction: column;
            margin-top: 0.2rem;
            padding-bottom: 1rem;
        }

        #routes .auditor {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 3rem 0.3rem 1rem;
        }

        #routes .auditor:hover {
            transition: 200ms;
            background-color: var(--color-background-gray);
        }

        #routes .route {
            align-items: center;
            border-bottom: 0.1rem dotted var(--color-border);
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }

        #routes .route > *:first-child {
            margin-right: auto;
        }

        #routes > .failed {
            border-color: var(--color-error);
        }

        #routes .route .status-icon {
            width: 1rem;
        }

        #routes > .failed .status-icon {
            color: var(--color-error);
        }

        #routes > .ok {
            border-color: var(--color-success);
        }

        #routes > .ok .status-icon {
            color: var(--color-success);
        }

        @media only screen and (min-width: 48rem) {
            body {
                padding: 1rem;
            }
            #aggregates {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
    <script type="module" language="javascript">
        const report = {!! $json !!};
        const numberFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });

        class BaseComponent extends HTMLElement {
            attributeChangedCallback(name, oldValue, newValue) {
                this.render();
            }
            connectedCallback = this.render;
        }

        class AggregateComponent extends BaseComponent {
            static observedAttributes = ['aggregate'];
            render = () => this.innerHTML = AggregateTemplateFactory.build(JSON.parse(this.attributes.aggregate.value));
        }

        class RouteComponent extends BaseComponent {
            static observedAttributes = ['route'];
            render = () => {
                const route = JSON.parse(this.attributes.route.value);

                const auditorRows = route.auditors.map((auditor) => AuditorRow.template(auditor));

                this.innerHTML = RouteRow.template(route) + auditorRows.join('');
            };
        }

        class AggregateTemplateFactory {
            static build = (aggregate) => {
                switch (true) {
                    case aggregate.aggregator === 'group': return ColumnChart.template(aggregate);
                    case aggregate.aggregator.includes('percentage'): return PercentageCard.template(aggregate);
                    default: return ResultCard.template(aggregate);
                }
            }
        }

        class RouteRow {
            static template = (route) => {
                const statusIcon = route.failed ? '&#10005;' : '&check;';

                return `
                    <div class="route">
                        <span>${route.name}</span>
                        <h2 class="text-xl">
                            ${numberFormatter.format(route.score)}
                            <sub class="text-lighter">/ ${numberFormatter.format(route.benchmark)}</sub>
                        </h2>
                        <span class="status-icon">${statusIcon}</span>
                    </div>
                `;
            }
        }

        class AuditorRow {
            static template = (auditor) => `
                <div class="auditor">
                    <sub class="text-lighter">${auditor.auditor.name}</sub>
                    <sub class="text-lighter">${numberFormatter.format(auditor.result)}</sub>
                </div>
            `;
        }

        class ResultCard {
            static template = (aggregate) => `
                <sub class="text-light">${aggregate.name}</sub>
                <h3>${numberFormatter.format(aggregate.result)}</h3>
            `;
        }

        class PercentageCard {
            static template = (aggregate) => `
                <sub class="text-light">${aggregate.name}</sub>
                <h3>${numberFormatter.format(aggregate.result)}%</h3>
            `;
        }

        class ColumnChart {
            static template = (aggregate) => {
                const total = aggregate.result.reduce((sum, bar) => sum + bar.result, 0);
                const bars = aggregate.result.map((bar) => ColumnChartBar.template(bar)).join('');

                return `
                    <sub class="text-light">${aggregate.name}</sub>
                    <div style="--total: ${total};">
                        ${bars}
                    </div>
                `;
            }
        }

        class ColumnChartBar {
            static template = (aggregate) => `
                <section title="${aggregate.name}: ${aggregate.result}" style="--value: ${aggregate.result};">
                    <span class="text-light">${aggregate.name}</span>
                </section>
            `;
        }

        customElements.define('aggregate-component', AggregateComponent);
        customElements.define('route-component', RouteComponent);

        const aggregateRootElement = document.getElementById('aggregates');
        report.aggregates?.forEach((aggregate) => {
            const element = document.createElement('aggregate-component');
            element.setAttribute('aggregate', JSON.stringify(aggregate));
            element.classList.add(aggregate.aggregator);
            aggregateRootElement.appendChild(element)
        });


        const routeRootElement = document.getElementById('routes');
        report.routes?.forEach((route) => {
            const element = document.createElement('route-component');
            element.classList.add(route.status);
            element.setAttribute('route', JSON.stringify(route));
            routeRootElement.appendChild(element)
        });
    </script>
</head>
<body>
    <main>
        <div class="content">
            <h1>Audit Routes report</h1>
            <h2 class="text-light">Aggregated results</h2>
        </div>
        <section id="aggregates"></section>
        <div class="content">
            <h2 class="text-light">Routes</h2>
        </div>
        <section id="routes"></section>
    </main>
</body>
</html>