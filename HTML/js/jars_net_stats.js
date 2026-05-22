/*
  Copyright (c) 2026 Douglas Graham
  All rights reserved.

  This file is part of the JARS Net Logger

  JARS Net Logger is free software: you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published by the Free
  Software Foundation, either version 3 of the License, or (at your option)
  any later version.

  This program is distributed in the hope that it will be useful, but WITHOUT
  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
  FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
  more details.

  You should have received a copy of the GNU General Public License along with
  this program. If not, see <https://www.gnu.org/licenses/>.

  REVISION 20260520.01

*/

const dir_path = '/jars';

const canvas_days = document.getElementById('r1_c1');
const canvas_top = document.getElementById('r1_c2');
const canvas_netop = document.getElementById('r2_c1');
const canvas_history = document.getElementById('r2_c2');

var chart_days = '';
var chart_top = '';
var chart_netop = '';
var chart_history = '';

Chart.defaults.font.family = "'Inclusive Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

// DOUGHNUT CHART OPTIONS

const doughnut_chart_options = {
  layout: { padding: 5 },
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    colors: {
      forceOverride: true
    },
    legend: {
      position: 'left',
      labels: {
        font: { size: 14 },
        color: 'rgba(255, 255, 255, 1)'
      }
    },
    title: {
      display: true,
      text: '',
      font: {
        size: 18,
        weight: 'normal'
      },
      color: 'rgba(51, 153, 255, 1)'
    }
  },
  scales: {
    x: {
      ticks: {
        display: false
      },
      title: {
        display: true,
        text: '',
        color: 'rgba(255, 255, 255, 1)',
        font: {
          size: 14
        }
      }
    }
  }
};

// BAR CHART OPTIONS

const bar_chart_options = {
  layout: { padding: 5 },
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    title: {
      display: true,
      text: '',
      font: {
        size: 18,
        weight: 'normal'
      },
      color: 'rgba(51, 153, 255, 1)'
    }
  },
  scales: {
    x: {
      ticks: {
        minRotation: 40,
        maxRotation: 40,
        autoSkip: false,
        color: 'rgba(255, 255, 255, 1)',
        font: {
          size: 14
        }
      },
      title: {
        display: true,
        text: '',
        color: 'rgba(255, 255, 255, 1)',
        font: {
          size: 14
        }
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        display: true,
        color: 'rgba(255, 255, 255, 0.25)',
        lineWidth: 1
      },
      ticks: {
        color: 'rgba(255, 255, 255, 1)',
        precision: 0
      }
    }
  }
};

// LINE CHART OPTIONS

const line_chart_options = {
  layout: { padding: 5 },
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        font: { size: 14 },
        color: 'rgba(255, 255, 255, 1)'
      }
    },
    title: {
      display: true,
      text: '',
      font: {
        size: 18,
        weight: 'normal',
      },
      color: 'rgba(51, 153, 255, 1)'
    },
  },
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false
  },
  scales: {
    x: {
      grid: {
        display: true,
        color: 'rgba(255, 255, 255, 0.25)',
        lineWidth: 1,
      },
      ticks: {
        minRotation: 40,
        maxRotation: 40,
        autoSkip: false,
        color: 'rgba(255, 255, 255, 1)',
        font: {
          size: 14
        },
      },
      title: {
        display: true,
        text: '',
        color: 'rgba(255, 255, 255, 1)',
        font: {
          size: 14
        },
      }
    },
    y: {
      type: 'logarithmic',
      stacked: false,
      beginAtZero: true,
      grid: {
        display: true,
        color: 'rgba(255, 255, 255, 0.25)',
        lineWidth: 1,
      },
      ticks: {
        color: 'rgba(255, 255, 255, 1)',
        precision: 0
      }
    }
  }
};

// CREATE INITIAL GRAPHS

async function create_charts() {
  const f_time_period = document.getElementById('time_period');
  const title = get_title();

  // VISITOR COUNT BY NIGHT

  var chart_options = structuredClone(doughnut_chart_options);;
  chart_options.plugins.title.text = 'Nightly - ' + title;
  chart_options.scales.x.title.text = 'Visitors';
  chart_days = await create_chart(canvas_days, 'doughnut', 'days', f_time_period.value, 1, 'Check-Ins', '', chart_options);

  // TOP VISITORS

  var chart_options = structuredClone(bar_chart_options);;
  chart_options.plugins.title.text = 'Top Visitors - ' + title;
  chart_options.scales.x.title.text = 'Visitor';
  chart_top = await create_chart(canvas_top, 'bar', 'top', f_time_period.value, 1, 'Check-Ins', '', chart_options);

  // VISITORS BY NET CONTROL

  var chart_options = structuredClone(line_chart_options);;
  chart_options.plugins.title.text = 'Net Control - ' + title;
  chart_options.scales.x.title.text = 'Net Control';
  chart_netop = await create_chart(canvas_netop, 'line', 'netop', f_time_period.value, 2, 'Total', 'Average', chart_options);

  // VISITOR COUNT 12 MONTH HISTORY

  var chart_options = structuredClone(line_chart_options);;
  chart_options.plugins.title.text = 'Visitors - 12 Month History';
  chart_options.scales.x.title.text = 'Month';
  chart_history = await create_chart(canvas_history, 'line', 'monthly', 12, 2, 'Total', 'Average', chart_options);

  update_count(title);
}

// CREATE NEW CHART

async function create_chart(canvas, chart_format, chart_type, chart_period, dataset_count, dataset1_label, dataset2_label, chart_options) {
  api_data = await get_data(chart_type, chart_period, dataset_count);

  if (dataset_count == 1) {
    var [label_list, data1_list] = api_data;
  } else {
    var [label_list, data1_list, data2_list] = api_data;
  }

  var chart_data = {
    labels: label_list,
    datasets: [{
      label: dataset1_label,
      data: data1_list
    }]
  }

  if (dataset_count == 2) {
    chart_data.datasets.push({'label': dataset2_label, 'data': data2_list});
  }

  if (chart_format == 'doughnut') {
    chart_data.datasets[0].borderWidth = 2;
    chart_data.datasets[0].hoverOffset = 20;
  }

  if (chart_format == 'bar') {
    chart_data.datasets[0].backgroundColor = [
      'rgba(54, 162, 235)',
      'rgba(255, 99, 132)',
      'rgba(255, 159, 64)',
      'rgba(255, 205, 86)',
      'rgba(75, 192, 192)',
      'rgba(153, 102, 255)',
      'rgba(201, 203, 207)'
    ]
  }

  if (chart_format == 'line') {
    for(i=0; i < dataset_count; i++) {
      chart_data.datasets[i].tension = .5;
    }
  }

  net_chart = new Chart(canvas, {
    type: chart_format,
    data: chart_data,
    options: chart_options
  })

  return net_chart;
}

// GET DATA

async function get_data(chart_type, chart_period, dataset_count) {
  const f_title_text = document.getElementById('title_text');
  const net_id = f_title_text.dataset.id;

  var response = await fetch(`${dir_path}/api/charts?net_id=${encodeURIComponent(net_id)}&chart_type=${encodeURIComponent(chart_type)}&chart_period=${encodeURIComponent(chart_period)}`);

  if (response.ok) {
    const api_data = await response.json();
    const sub_data = api_data.data;
    const label_list = [];
    const data1_list = [];
    const data2_list = [];

    sub_data.forEach(row => {
      label_list.push(row.key);
      data1_list.push(row.count);

      if (dataset_count == 2) {
        data2_list.push((row.count / row.nets).toFixed(1));
      }
    })

    if (dataset_count == 1) {
      return [label_list, data1_list];
    } else {
      return [label_list, data1_list, data2_list];
    }
  }
}

// GET TITLE

function get_title() {
  const f_time_period = document.getElementById('time_period');
  var title = '';

  if (f_time_period.value === 'mtd') {
    title = 'Month to Date';
  } else if (f_time_period.value === 'ytd') {
    title = 'Year to Date';
  } else if (f_time_period.value === '365') {
    title = '1 Year';
  } else {
    title = f_time_period.value + ' Days';
  }

  return title;
}

// UPDATE CHARTS ON PERIOD CHANGE

async function update_charts() {
  const f_title_text = document.getElementById('title_text');
  const net_id = f_title_text.dataset.id;
  const f_time_period = document.getElementById('time_period');
  const title = get_title();

  api_data = await get_data('days', f_time_period.value, 1);

  var [label_list, data1_list] = api_data;

  chart_days.options.plugins.title.text = 'Nightly - ' + title;
  chart_days.data.labels = label_list;
  chart_days.data.datasets[0].data = data1_list;
  chart_days.update();

  api_data = await get_data('top', f_time_period.value, 1);

  var [label_list, data1_list] = api_data;

  chart_top.options.plugins.title.text = 'Top Visitors - ' + title;
  chart_top.data.labels = label_list;
  chart_top.data.datasets[0].data = data1_list;
  chart_top.update();

  api_data = await get_data('netop', f_time_period.value, 2);

  var [label_list, data1_list, data2_list] = api_data;

  chart_netop.options.plugins.title.text = 'Net Control - ' + title;
  chart_netop.data.labels = label_list;
  chart_netop.data.datasets[0].data = data1_list;
  chart_netop.data.datasets[1].data = data2_list;
  chart_netop.update();

  update_count(title);
}

// UPDATE TOTAL COUNT AT TOP OF PAGE

async function update_count(title) {
  const f_title_text = document.getElementById('title_text');
  const net_id = f_title_text.dataset.id;
  const f_time_period = document.getElementById('time_period');
  const f_vis_count = document.getElementById('vis_count');

  var response = await fetch(`${dir_path}/api/counts?net_id=${encodeURIComponent(net_id)}&count_type=checkins&count_period=${encodeURIComponent(f_time_period.value)}`);

  if (response.ok) {
    const api_data = await response.json();
    f_vis_count.textContent = `Total Visitors - ${title}: ${api_data.count}`;
  } else {
    f_vis_count.textContent = '';
  }
}

create_charts();
