export default {
  methods: {
    resizeTextarea (e) {
      // console.log(e)
      e.target.style.height = 'auto'
      e.target.style.height = `${e.target.scrollHeight}px`
    },
  },
}
