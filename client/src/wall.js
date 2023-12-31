/* eslint-disable eqeqeq */
import $ from 'jquery'
import i18n from '@/helper/i18n'
import { expose } from '@/utils'
import { pulseError } from '@/script'
import { deletePost } from '@/api/wall'

async function delWallpost (postId, module, wallId) {
  try {
    await deletePost(module, wallId, postId)
    $(`.wallpost-${postId}`).remove()
  } catch (e) {
    pulseError(i18n('error_unexpected'))
  }
}

function finishImage (file) {
  $('#wallpost-attach').append(`<input type="hidden" name="attach[]" value="image-${file}" />`)
  $('#attach-preview div:last').remove()
  $('.attach-load').remove()
  $('#attach-preview').append(`<a rel="wallpost-gallery" class="preview-thumb" href="/images/wallpost/${file}"><img src="/images/wallpost/thumb_${file}" height="60" /></a>`)
  $('#attach-preview').append('<div class="clear"></div>')
  $('#attach-preview a').fancybox()
  resetUploader()
}

function resetUploader () {
  $('#wallpost-loader').html('')
  $('a.attach-load').remove()
}

export function initWall (module, wallId) {
  $('#wallpost-text').autosize()
  $('#wallpost-text').on('focus', function () {
    $('#wallpost-submit').show()
  })

  $('#wallpost-attach-trigger').on('change', function () {
    $('#attach-preview div:last').remove()
    $('#attach-preview').append('<a rel="wallpost-gallery" class="preview-thumb attach-load" href="#" onclick="return false;">&nbsp;</a>')
    $('#attach-preview').append('<div class="clear"></div>')
    $('#wallpost-attachimage-form').trigger('submit')
  })

  $('#wallpost-text').on('blur', function () {
    $('#wallpost-submit').show()
  })
  $('#wallpost-post').on('submit', function (ev) {
    ev.preventDefault()
  })
  $('#wallpost-attach-image').button().on('click', function () {
    $('#wallpost-attach-trigger').trigger('click')
  })
  $('#wall-submit').button().on('click', function (ev) {
    ev.preventDefault()
    if (($('#wallpost-text').val() != '' && $('#wallpost-text').val() != i18n('wall.message_placeholder')) || $('#attach-preview a').length > 0) {
      $('.wall-posts table tr:first').before('<tr><td colspan="2" class="load">&nbsp;</td></tr>')

      let attach = ''
      $('#wallpost-attach input').each(function () {
        attach = `${attach}:${$(this).val()}`
      })
      if (attach.length > 0) {
        attach = attach.substring(1)
      }

      let text = $('#wallpost-text').val()
      if (text == i18n('wall.message_placeholder')) {
        text = ''
      }

      $.ajax({
        url: `/xhrapp?app=wallpost&m=post&table=${module}&id=${wallId}`,
        type:
          'POST',
        data:
          {
            text: text,
            attach: attach,
          },
        dataType: 'JSON',
        success:
          function (data) {
            $('#wallpost-attach').html('')
            if (data.status == 1) {
              $('.wall-posts').html(data.html)
              $('.preview-thumb').fancybox()
              if (data.script != undefined) {
                $.globalEval(data.script)
              }
            }
          },
      })

      $('#wallpost-text').val('')
      $('#attach-preview').html('')
      $('#wallpost-attach').html('')
      $('#wallpost-text')[0].focus()
      $('#wallpost-text').css('height', '33px')
    }
  })
  $('#wallpost-attach-trigger').on('focus', function () {
    $('#wall-submit')[0].focus()
  })
  $.ajax({
    url: `/xhrapp?app=wallpost&m=update&table=${module}&id=${wallId}&last=0`,
    dataType:
      'JSON',
    success:

      function (data) {
        if (data.status == 1) {
          $('.wall-posts').html(data.html)
          $('.preview-thumb').fancybox()
        }
      },
  })
  // these are needed in global namespace because of legacy XHR code relying on them:
  expose({ delWallpost, finishImage, resetUploader })
}
