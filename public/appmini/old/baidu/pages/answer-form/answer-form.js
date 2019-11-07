const app = getApp();
Page({
  data: {
    form_submit_loading: false
  },

  onLoad() {},

  onShow() {
    swan.setNavigationBarTitle({ title: app.data.common_pages_title.answer_form });
    this.init();
  },

  // 初始化
  init() {
    var user = app.get_user_cache_info(this, "init");
    // 用户未绑定用户则转到登录页面
    if (app.user_is_need_login(user)) {
      swan.redirectTo({
        url: "/pages/login/login?event_callback=init"
      });
      return false;
    }
  },

  /**
   * 表单提交
   */
  formSubmit(e) {
    // 数据验证
    var validation = [{ fields: 'name', msg: '请填写联系人' }, { fields: 'tel', msg: '请填写联系电话' }, { fields: 'content', msg: '请填写内容' }];
    if (app.fields_check(e.detail.value, validation)) {
      swan.showLoading({ title: '提交中...' });
      this.setData({ form_submit_loading: true });

      // 网络请求
      swan.request({
        url: app.get_request_url('add', 'answer'),
        method: 'POST',
        data: e.detail.value,
        dataType: 'json',
        header: { 'content-type': 'application/x-www-form-urlencoded' },
        success: res => {
          swan.hideLoading();

          if (res.data.code == 0) {
            app.showToast(res.data.msg, "success");
            setTimeout(function () {
              swan.redirectTo({
                url: "/pages/user-answer-list/user-answer-list"
              });
            }, 2000);
          } else {
            this.setData({ form_submit_loading: false });

            app.showToast(res.data.msg);
          }
        },
        fail: () => {
          swan.hideLoading();
          this.setData({ form_submit_loading: false });

          app.showToast('服务器请求出错');
        }
      });
    }
  }

});