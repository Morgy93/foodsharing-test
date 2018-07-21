/* eslint-disable eqeqeq */
import $ from 'jquery'
import _ from 'underscore'

import storage from '@/storage'
import { ajax, GET } from '@/script'

const info = {
  /*
   * preselect elements to reduce dom querys
   */
  $infobar: null,
  $badge: null,
  $linklist: null,
  $linkwrapper: null,
  $allWrapper: null,

  hasTouchEvent: false,
  /*
   * an array of the services that the heartbeat have to call
   */
  services: null,

  /*
   * little var for everyone to play with user data
   */
  user: null,

  /*
   * count for heartbeat times
   */
  hbCount: 0,

  /*
   * here an array to store recieved data for each item
   */
  data: null,

  /*
   * in this array we store the last refreshing times of each info item
   */
  refreshTime: [],

  /*
   * after this time we want to refrsh the info content
   */
  refreshTimeout: 30000,

  hbXhr: null,

  startupTimeout: false,

  /*
   * pseudo construct
   */
  init: function () {
    this.services = []
    // removed code for the old topbar
  },

  /*
   * function to init all the ui stuff
   */
  initEvents: function () {
    $('html').click(function () {
      info.$allWrapper.hide()
    })

    this.$infobar.children('li').each(function () {
      var $this = $(this)
      var type = $this.attr('class')

      $this.children('span').click(function (event) {
        event.stopPropagation()
      })

      $this.click(function (event) {
        event.stopPropagation()
        info.$infobar.children('li').removeClass('touched')
        if (!info.hasTouchEvent) {
          if (info.$linkwrapper[type].is(':visible')) {
            info.$linkwrapper[type].hide()
          } else {
            info.$allWrapper.hide()
            info.refresh(type)
            info.$linkwrapper[type].show()
          }
        }
      })
    })
  },

  delBell: function (id) {
    ajax.req('bell', 'delbell', {
      data: { id: id },
      loader: false
    })

    var $item = $('#belllist-' + id)

    $item.animate({
      marginBottom: '-62px',
      opacity: 0
    }, 200, function () {
      $item.remove()
    })
  },

  /*
   * function to set and display the badge number in top of an info item
   */
  badge: function (type, val) {
    this.$badge[type].text(val)
    if (val > 0) {
      this.$badge[type].css('display', 'inline-block')
    } else {
      this.$badge[type].css('display', 'none')
    }
  },

  /*
   * function to increment current badge number to specific type
   */
  badgeInc: function (type) {
    // following line got commented out, because it is part of the old topbar
    // probably this file can get removed entirely anyway
    // -------------
    // if (this.$badge[type] != undefined) {
    //   let val = parseInt(this.$badge[type].text())
    //   val++
    //   this.$badge[type].text(val + '')
    //   if (val > 0) {
    //     this.$badge[type].css('display', 'inline-block')
    //   } else {
    //     this.$badge[type].css('display', 'none')
    //   }
    // } else {
    //   console.log(type + ' is undefined')
    // }
  },

  /**
   * Method to add an polling service
   * options are send as GET Parameter to the module action
   *
   * the there are 3 polling speed options {speed:slow|moderate|fast}
   * default is slow = every 10 seconds
   *     moderate is slow/4  => 2.5 seconds as default
   *     fast is slow/20   => 0.5 seconds as default
   *
   * option {premethod:[methodName]} with this option you can define an method which is called before the session is locked for writing
   */
  addService: function (app, method, options) {
    this.services.push({
      a: app,
      m: method,
      o: options
    })

    this.restart()
  },

  /**
   * remove an polling service
   */
  removeService: function (app, method) {
    var tmp = []
    for (var i = 0; i < info.services.length; i++) {
      if (!(info.services[i].a == app || info.services.m == method)) {
        tmp.push(info.services[i])
      }
    }
    this.services = tmp
    this.restart()
  },

  /**
   * modify service parameter
   */
  editService: function (app, method, options) {
    var tmp = []
    for (var i = 0; i < info.services.length; i++) {
      if (!(info.services[i].a == app || info.services.m == method)) {
        tmp.push(info.services[i])
      }
    }

    /**
     * if the service is not in the list just add it
     */
    tmp.push({
      a: app,
      m: method,
      o: options
    })

    info.services = tmp
    this.restart()
  },

  /**
   * restart the heartbead
   */
  restart: function () {
    if (_.isNull(this.hbXhr) !== true) {
      info.hbCount = 0
      this.hbXhr.abort()
    }
  },

  /**
   * continiously checking for updates
   */
  heartbeat: function () {
    if (this.startupTimeout) {
      this.hbXhr = ajax.req('info', 'heartbeat', {
        loader: false,
        data: {
          c: info.hbCount,

          // add services to param list
          s: info.services,
          p: GET('page')
        },
        success: function (ret) {
          if (info.hbCount == 0) {
            info.user = ret.user
          }

          if (ret.info != undefined && ret.info.length > 0) {
            for (var i = 0; i < ret.info.length; i++) {
              // set badge count for each item
              info.badge(ret.info[i].type, ret.info[i].badge)

              // store specific data for each item
              info.data[ret.info[i].type] = ret.info[i].data
            }
          }
        },
        complete: function () {
          info.heartbeat()
          info.hbCount++
        }
      })
    }
  },

  /**
   * show status loading on specific info item
   */
  showLoader: function (item) {
    this.$linklist[item].prepend('<li class="loader"><i class="fa fa-spinner fa-spin"></i></li>')
  },

  /**
   * hide status
   */
  hideLoader: function (item) {
    this.$linklist[item].children('.loader').remove()
  },

  /**
   * check if its time for refresh reload the info content
   */
  refresh: function (item) {
    if (info.refreshTime[item] == undefined) {
      info.refreshTime[item] = 0
    }
    info.badge(item, 0)
    info.$badge[item].hide()

    if ($.now() - info.refreshTime[item] > info.refreshTimeout) {
      storage.del('badge')
      info.showLoader(item)
      info.refreshTime[item] = $.now()
      ajax.req(item, 'infobar', {
        loader: false,
        data: info.data[item],
        success: function (ret) {
          info.hideLoader(item)
          if (ret.html != undefined) {
            info.$linklist[item].html(ret.html)
            info.badge(item, 0)
            info.$badge[item].hide()
          }
        }
      })
    }
  }
}

export default info
