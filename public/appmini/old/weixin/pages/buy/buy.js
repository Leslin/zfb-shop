const app = getApp();
Page({
  data: {
    data_list_loding_status: 1,
    buy_submit_disabled_status: false,
    data_list_loding_msg: '',
    params: null,
    payment_list: [],
    goods_list: [],
    address: null,
    address_id: 0,
    total_price: 0,
    user_note_value: '',
    is_first: 1,
    extension_data: [],
    payment_id: 0,
    common_order_is_booking: 0,
  },
  onLoad(params) {
    if((params.data || null) == null || app.get_length(JSON.parse(params.data)) == 0)
    {
      wx.alert({
        title: '温馨提示',
        content: '订单信息有误',
        buttonText: '确认',
        success: () => {
          wx.navigateBack();
        },
      });
    } else {
      this.setData({ params: JSON.parse(params.data)});

      // 删除地址缓存
      wx.removeStorageSync(app.data.cache_buy_user_address_select_key);
    }
  },

  onShow() {
    wx.setNavigationBarTitle({title: app.data.common_pages_title.buy});
    this.init();
    this.setData({is_first: 0});
  },

  // 获取数据列表
  init() {
    // 本地缓存地址
    if(this.data.is_first == 0)
    {
      var cache_address = wx.getStorageSync(app.data.cache_buy_user_address_select_key);
      if((cache_address || null) != null)
      {
        this.setData({
          address: cache_address,
          address_id: cache_address.id
        });
      } else {
        this.setData({
          address: null,
          address_id: 0
        });
      }
    }

    // 加载loding
    wx.showLoading({title: '加载中...'});
    this.setData({
      data_list_loding_status: 1
    });

    var data = this.data.params;
    data['address_id'] = this.data.address_id;
    data['payment_id'] = this.data.payment_id;
    wx.request({
      url: app.get_request_url("index", "buy"),
      method: "POST",
      data: data,
      dataType: "json",
      success: res => {
        wx.hideLoading();
        if (res.data.code == 0) {
          var data = res.data.data;
          if (data.goods_list.length == 0)
          {
            this.setData({data_list_loding_status: 0});
          } else {
            this.setData({
              goods_list: data.goods_list,
              total_price: data.base.actual_price,
              extension_data: data.extension_data || [],
              data_list_loding_status: 3,
              common_order_is_booking: data.common_order_is_booking || 0,
            });

            // 地址
            if (this.data.address == null || this.data.address_id == 0) {
              if((data.base.address || null) != null) {
                this.setData({
                  address: data.base.address,
                  address_id: data.base.address.id,
                });

                wx.setStorage({
                  key: app.data.cache_buy_user_address_select_key,
                  data: data.base.address,
                });
              }
            }

            // 支付方式
            this.payment_list_data(data.payment_list);
          }
        } else {
          this.setData({
            data_list_loding_status: 2,
            data_list_loding_msg: res.data.msg,
          });
          app.showToast(res.data.msg);
        }
      },
      fail: () => {
        wx.hideLoading();
        this.setData({
          data_list_loding_status: 2,
          data_list_loding_msg: '服务器请求出错',
        });
        
        app.showToast("服务器请求出错");
      }
    });
  },

  // 用户留言事件
  bind_user_note_event(e) {
    this.setData({user_note_value: e.detail.value});
  },

  // 提交订单
  buy_submit_event(e) {
    // 表单数据
    var data = this.data.params;
    data['address_id'] = this.data.address_id;
    data['payment_id'] = this.data.payment_id;
    data['user_note'] = this.data.user_note_value;

    // 数据验证
    var validation = [
      { fields: 'address_id', msg: '请选择地址' }
    ];
    if (this.data.common_order_is_booking != 1) {
      validation.push({ fields: 'payment_id', msg: '请选择支付方式' });
    }
    if (app.fields_check(data, validation)) {
      // 加载loding
      wx.showLoading({title: '提交中...'});
      this.setData({ buy_submit_disabled_status: true });

      wx.request({
        url: app.get_request_url("add", "buy"),
        method: "POST",
        data: data,
        dataType: "json",
        success: res => {
          wx.hideLoading();
          if (res.data.code == 0) {
            if (res.data.data.order.status == 1) {
              wx.redirectTo({
                url: '/pages/user-order/user-order?is_pay=1&order_id=' + res.data.data.order.id
              });
            } else {
              wx.redirectTo({url: '/pages/user-order/user-order'});
            }
          } else {
            app.showToast(res.data.msg);
            this.setData({ buy_submit_disabled_status: false });
          }
        },
        fail: () => {
          wx.hideLoading();
          this.setData({buy_submit_disabled_status: false});
          
          app.showToast("服务器请求出错");
        }
      });
    }
  },

  // 支付方式选择
  payment_event(e) {
    this.setData({ payment_id: e.currentTarget.dataset.value});
    this.payment_list_data(this.data.payment_list);
    this.init();
  },

  // 支付方式数据处理
  payment_list_data(data) {
    if (this.data.payment_id != 0) {
      for (var i in data) {
        if (data[i]['id'] == this.data.payment_id) {
          data[i]['selected'] = 'selected';
        } else {
          data[i]['selected'] = '';
        }
      }
    }
    this.setData({payment_list: data || []});
  }

});
