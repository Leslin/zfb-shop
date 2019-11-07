const app = getApp();
Page({
  data: {
    data_list_loding_status: 1,
    data_list_loding_msg: '',
    data_bottom_line_status: false,
    data_list: [],
    swipe_index: null,
    total_price: '0.00',
    is_selected_all: false,
    buy_submit_disabled_status: true
  },

  onShow() {
    swan.setNavigationBarTitle({ title: app.data.common_pages_title.cart });
    this.init();
  },

  init(e) {
    var user = app.get_user_cache_info(this, "init");
    // 用户未绑定用户则转到登录页面
    var msg = user == false ? '授权用户信息' : '绑定手机号码';
    if (app.user_is_need_login(user)) {
      swan.showModal({
        title: '温馨提示',
        content: msg,
        confirmText: '确认',
        cancelText: '暂不',
        success: result => {
          if (result.confirm) {
            swan.navigateTo({
              url: "/pages/login/login?event_callback=init"
            });
          } else {
            this.setData({
              data_list_loding_status: 0,
              data_bottom_line_status: false,
              data_list_loding_msg: '请先' + msg
            });
          }
        }
      });
    } else {
      this.get_data();
    }
  },

  // 获取数据
  get_data() {
    this.setData({
      data_list_loding_status: 1,
      total_price: '0.00',
      is_selected_all: false,
      buy_submit_disabled_status: true
    });

    swan.request({
      url: app.get_request_url("index", "cart"),
      method: "POST",
      data: {},
      dataType: "json",
      success: res => {
        swan.stopPullDownRefresh();
        if (res.data.code == 0) {
          var data = res.data.data;
          if (data.length > 0) {
            for (var i in data) {
              data[i]['right'] = [{ type: 'edit', text: '加入收藏' }, { type: 'delete', text: '删除' }];
            }
          }
          this.setData({
            data_list: data,
            data_list_loding_status: data.length == 0 ? 0 : 3,
            data_bottom_line_status: true,
            data_list_loding_msg: '购物车空空如也'
          });
        } else {
          this.setData({
            data_list_loding_status: 2,
            data_bottom_line_status: false,
            data_list_loding_msg: res.data.msg
          });
          app.showToast(res.data.msg);
        }
      },
      fail: () => {
        swan.stopPullDownRefresh();
        this.setData({
          data_list_loding_status: 2,
          data_bottom_line_status: false,
          data_list_loding_msg: '服务器请求出错'
        });

        app.showToast("服务器请求出错");
      }
    });
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.init();
  },

  // 数量输入事件
  goods_buy_number_blur(e) {
    var index = e.currentTarget.dataset.index || 0;
    var buy_number = parseInt(e.detail.value) || 1;
    this.goods_buy_number_func(index, buy_number);
  },

  // 数量操作事件
  goods_buy_number_event(e) {
    var index = e.currentTarget.dataset.index || 0;
    var type = parseInt(e.currentTarget.dataset.type) || 0;
    var temp_buy_number = parseInt(this.data.data_list[index]['stock']);
    if (type == 0) {
      var buy_number = temp_buy_number - 1;
    } else {
      var buy_number = temp_buy_number + 1;
    }
    this.goods_buy_number_func(index, buy_number);
  },

  // 数量处理方法
  goods_buy_number_func(index, buy_number) {
    var temp_data_list = this.data.data_list;
    var buy_min_number = parseInt(temp_data_list[index]['buy_min_number']) || 1;
    var buy_max_number = parseInt(temp_data_list[index]['buy_max_number']) || 0;
    var inventory = parseInt(temp_data_list[index]['inventory']);
    var inventory_unit = temp_data_list[index]['inventory_unit'];
    if (buy_number < buy_min_number) {
      buy_number = buy_min_number;
      if (buy_min_number > 1) {
        app.showToast('起购' + buy_min_number + inventory_unit);
        return false;
      }
    }
    if (buy_max_number > 0 && buy_number > buy_max_number) {
      buy_number = buy_max_number;
      app.showToast('限购' + buy_max_number + inventory_unit);
      return false;
    }
    if (buy_number > inventory) {
      buy_number = inventory;
      app.showToast('库存数量' + inventory + inventory_unit);
      return false;
    }

    if (temp_data_list[index]['stock'] == 1 && buy_number == 1) {
      return false;
    }

    // 更新数据库
    swan.request({
      url: app.get_request_url("stock", "cart"),
      method: "POST",
      data: { "id": temp_data_list[index]['id'], "goods_id": temp_data_list[index]['goods_id'], "stock": buy_number },
      dataType: "json",
      success: res => {
        swan.stopPullDownRefresh();
        if (res.data.code == 0) {
          temp_data_list[index]['stock'] = buy_number;
          this.setData({ data_list: temp_data_list });

          // 选择处理
          this.selected_calculate();
        } else {
          app.showToast(res.data.msg);
        }
      },
      fail: () => {
        app.showToast("服务器请求出错");
      }
    });
  },

  // 收藏事件
  goods_favor_event(id, goods_id, type) {
    swan.request({
      url: app.get_request_url('favor', 'goods'),
      method: 'POST',
      data: { "id": goods_id, "is_mandatory_favor": 1 },
      dataType: 'json',
      success: res => {
        if (res.data.code == 0) {
          this.cart_delete(id, type);
        } else {
          app.showToast(res.data.msg);
        }
      },
      fail: () => {
        app.showToast("服务器请求出错");
      }
    });
  },

  // 删除操作事件
  cart_delete_event(e) {
    var id = e.currentTarget.dataset.id || null;
    if (id !== null) {
      swan.showModal({
        title: '温馨提示',
        content: '删除后不可恢复，确定继续吗?',
        confirmText: '确认',
        cancelText: '暂不',
        success: result => {
          if (result.confirm) {
            this.cart_delete(id, 'delete');
          }
        }
      });
    } else {
      app.showToast("参数有误");
    }
  },

  // 购物车删除
  cart_delete(id, type) {
    swan.request({
      url: app.get_request_url('delete', 'cart'),
      method: 'POST',
      data: { "id": id },
      dataType: 'json',
      success: res => {
        if (res.data.code == 0) {
          var temp_data_list = this.data.data_list;
          temp_data_list.splice(this.data.swipe_index, 1);
          this.setData({
            data_list: temp_data_list,
            swipe_index: null,
            data_list_loding_status: temp_data_list.length == 0 ? 0 : this.data.data_list_loding_status
          });

          app.showToast(type == 'delete' ? '删除成功' : '收藏成功', 'success');
        } else {
          app.showToast(type == 'delete' ? '删除失败' : '收藏失败');
        }
      },
      fail: () => {
        app.showToast("服务器请求出错");
      }
    });
  },

  // 选中处理
  selectedt_event(e) {
    var type = e.currentTarget.dataset.type || null;
    if (type != null) {
      var temp_data_list = this.data.data_list;
      var temp_is_selected_all = this.data.is_selected_all;
      switch (type) {
        // 批量操作
        case 'all':
          temp_is_selected_all = temp_is_selected_all == true ? false : true;
          for (var i in temp_data_list) {
            temp_data_list[i]['selected'] = temp_is_selected_all;
          }
          break;

        // 节点操作
        case 'node':
          var index = e.currentTarget.dataset.index || 0;
          temp_data_list[index]['selected'] = temp_data_list[index]['selected'] == true ? false : true;
          break;
      }

      this.setData({
        data_list: temp_data_list,
        is_selected_all: temp_is_selected_all
      });

      // 选择处理
      this.selected_calculate();
    }
  },

  // 选中计算
  selected_calculate() {
    var total_price = 0;
    var selected_count = 0;
    var temp_data_list = this.data.data_list;
    for (var i in temp_data_list) {
      if ((temp_data_list[i]['selected'] || false) == true) {
        total_price += temp_data_list[i]['stock'] * temp_data_list[i]['price'];
        selected_count++;
      }
    }

    this.setData({
      total_price: total_price.toFixed(2),
      buy_submit_disabled_status: selected_count <= 0,
      is_selected_all: selected_count >= temp_data_list.length
    });
  },

  // 结算
  buy_submit_event(e) {
    var selected_count = 0;
    var ids = [];
    var temp_data_list = this.data.data_list;
    for (var i in temp_data_list) {
      if ((temp_data_list[i]['selected'] || false) == true) {
        ids.push(temp_data_list[i]['id']);
        selected_count++;
      }
    }

    if (selected_count <= 0) {
      app.showToast("请选择商品");
      return false;
    }

    // 进入订单确认页面
    var data = {
      "buy_type": "cart",
      "ids": ids.join(',')
    };
    swan.navigateTo({
      url: '/pages/buy/buy?data=' + JSON.stringify(data)
    });
  }

});