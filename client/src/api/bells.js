import { get, patch, remove } from './base'

// wrapper around the legacy SearchXHR method
export async function getBellList () {
  return (await get('/../xhrapp.php?app=bell&m=infobar')).data.list
}

export function deleteBell (id) {
  return remove(`/bells/${id}`)
}

export function markBellsAsRead (ids) {
  return patch('/bells/', {
    ids: ids
  })
}
