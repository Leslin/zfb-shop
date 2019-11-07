const app = getApp();
Page({
  data: {
    params: null,
    user: null,
    mobile: null,
    verify_submit_text: '获取验证码',
    verify_loading: false,
    verify_disabled: false,
    form_submit_loading: false,
    verify_time_total: 60,
    temp_clear_time: null
  },

  /**
   * 页面加载初始化
   */
  onLoad(option) {
    // 设置用户信息
    this.setData({
      params: option,
      user: app.get_user_cache_info() || null
    });

    // 标题设置
    swan.setNavigationBarTitle({ title: this.data.user == null ? '授权用户信息' : '手机绑定' });
  },

  /**
   * 登录授权事件
   */
  get_user_info_event() {
    app.user_auth_login(this, 'user_auth_back_event');
  },

  /**
   * 授权返回事件
   */
  user_auth_back_event() {
    var user = app.get_user_cache_info();
    this.setData({ user: user || null });
    if (app.user_is_need_login(user) == false) {
      swan.navigateBack();
    }
  },

  /**
   * 输入手机号码事件
   */
  bind_key_input(e) {
    this.setData({ mobile: e.detail.value });
  },

  /**
   * 短信验证码发送
   */
  verify_send() {
    // 数据验证
    var validation = [{ fields: 'mobile', msg: '请填写手机号码' }];
    if (app.fields_check(this.data, validation)) {
      // 网络请求
      var $this = this;
      swan.showLoading({ title: '发送中...' });
      this.setData({ verify_submit_text: '发送中', verify_loading: true, verify_disabled: true });

      swan.request({
        url: app.get_request_url('regverifysend', 'user'),
        method: 'POST',
        data: { mobile: this.data.mobile },
        dataType: 'json',
        header: { 'content-type': 'application/x-www-form-urlencoded' },
        success: res => {
          swan.hideLoading();
          if (res.data.code == 0) {
            this.setData({ verify_loading: false });
            var temp_time = this.data.verify_time_total;
            this.data.temp_clear_time = setInterval(function () {
              if (temp_time <= 1) {
                clearInterval($this.data.temp_clear_time);
                $this.setData({ verify_submit_text: '获取验证码', verify_disabled: false });
              } else {
                temp_time--;
                $this.setData({ verify_submit_text: '剩余 ' + temp_time + ' 秒' });
              }
            }, 1000);
          } else {
            this.setData({ verify_submit_text: '获取验证码', verify_loading: false, verify_disabled: false });

            app.showToast(res.data.msg);
          }
        },
        fail: () => {
          swan.hideLoading();
          this.setData({ verify_submit_text: '获取验证码', verify_loading: false, verify_disabled: false });

          app.showToast("服务器请求出错");
        }
      });
    }
  },

  /**
   * 表单提交
   */
  formSubmit(e) {
    // 邀请人参数
    var params = swan.getStorageSync(app.data.cache_launch_info_key) || null;

    // 数据验证
    var validation = [{ fields: 'mobile', msg: '请填写手机号码' }, { fields: 'verify', msg: '请填写验证码' }, { fields: 'baidu_openid', msg: '授权id不能为空' }];
    e.detail.value['baidu_openid'] = this.data.user.baidu_openid;
    e.detail.value['nickname'] = this.data.user.nickname;
    e.detail.value['avatar'] = this.data.user.avatar;
    e.detail.value['province'] = this.data.user.province;
    e.detail.value['city'] = this.data.user.city;
    e.detail.value['gender'] = this.data.user.gender;
    e.detail.value['app_type'] = 'baidu';
    e.detail.value['referrer'] = (params == null) ? this.data.user.referrer || 0 : params.referrer || 0;
    if (app.fields_check(e.detail.value, validation)) {
      swan.showLoading({ title: '处理中...' });
      this.setData({ form_submit_loading: true });

      // 网络请求
      swan.request({
        url: app.get_request_url('reg', 'user'),
        method: 'POST',
        data: e.detail.value,
        dataType: 'json',
        header: { 'content-type': 'application/x-www-form-urlencoded' },
        success: res => {
          swan.hideLoading();

          if (res.data.code == 0 && (res.data.data || null) != null) {
            clearInterval(this.data.temp_clear_time);
            app.showToast(res.data.msg, 'success');

            swan.setStorage({
              key: app.data.cache_user_info_key,
              data: res.data.data
            });

            var event_callback = this.data.params.event_callback || null;
            setTimeout(function () {
              // 触发回调函数
              if (event_callback != null) {
                getCurrentPages()[getCurrentPages().length - 2][event_callback]();
              }
              swan.navigateBack();
            }, 1000);
          } else {
            this.setData({ form_submit_loading: false });

            app.showToast(res.data.msg);
          }
        },
        fail: () => {
          swan.hideLoading();
          this.setData({ form_submit_loading: false });

          app.showToast("服务器请求出错");
        }
      });
    }
  }

});