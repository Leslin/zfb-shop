const app = getApp();
Page({
  data: {
    tab_active: 0,
    tab_active_text_color: '#d2364c',
    tab_active_line_color: '#d2364c',
    data_list_loding_status: 1,
    data_bottom_line_status: false,
    data_list: [],
    data_content: [],
  },

  onShow() {
    wx.setNavigationBarTitle({title: app.data.common_pages_title.goods_category});
    this.init();
  },

  // 获取数据
  init() {
    // 加载loding
    this.setData({
      data_list_loding_status: 1,
    });

    // 加载loding
    wx.request({
      url: app.get_request_url("category", "goods"),
      method: "POST",
      data: {},
      dataType: "json",
      header: { 'content-type': 'application/x-www-form-urlencoded' },
      success: res => {
        wx.stopPullDownRefresh();
        if (res.data.code == 0) {
            var data = res.data.data;
            var data_content = [];
            if (data.length > 0)
            {
              data[0]['active'] = 'nav-active';
              data_content = data[0]['items'];
            }
            this.setData({
              data_list: data,
              data_content: data_content,
              data_list_loding_status: data.length == 0 ? 0 : 3,
              data_bottom_line_status: true,
            });
          } else {
            this.setData({
              data_list_loding_status: 0,
              data_bottom_line_status: true,
            });

            app.showToast(res.data.msg);
          }
      },
      fail: () => {
        wx.stopPullDownRefresh();
        this.setData({
          data_list_loding_status: 2,
          data_bottom_line_status: true,
        });

        app.showToast("服务器请求出错");
      }
    });
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.init();
  },

  // 导航事件
  nav_event(e) {
    var index = e.currentTarget.dataset.index;
    var temp_data = this.data.data_list;
    for(var i in temp_data)
    {
      temp_data[i]['active'] = (index == i) ? 'nav-active' : '';
    }
    this.setData({
      data_list: temp_data,
      data_content: temp_data[index]['items'],
    });
  },

  // 事件
  category_event(e) {
    wx.navigateTo({ url: '/pages/goods-search/goods-search?category_id=' + e.currentTarget.dataset.value});
  }
});