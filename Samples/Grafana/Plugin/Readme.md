# Grafana plugin development

Even though Grafana includes many ready-production panel visualization plugins, one of the most common in metrics dashboards is funnel and unfortunately is not available for Grafana natively.

However as long as you have some knowledge in typescript and React you can implement them by your own, and thats exactly what I did.

All I had to do was to successfully connect the plugin structure of a Grafana Plugin with the awesome [echarts library](https://echarts.apache.org/examples/en/index.html).

This was the end result.

### Main dashboard
![Funnel visualization plugin](https://github.com/Rodricity/resume/blob/master/Samples/Grafana/Plugin/funnel-plugin.png)