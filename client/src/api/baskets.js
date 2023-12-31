import { get, post, remove, put } from './base'

export async function getBaskets () {
  const baskets = (await get('/baskets?type=mine')).baskets
  return baskets.map(basket => {
    basket.createdAt = new Date(basket.createdAt * 1000)
    basket.updatedAt = new Date(basket.updatedAt * 1000)
    basket.requests = basket.requests.map(request => {
      request.time = new Date(request.time * 1000)
      return request
    })
    return basket
  })
}

export async function requestBasket (basketId, message) {
  return (post(`/baskets/${basketId}/request`, {
    message: message,
  }))
}

export async function withdrawBasketRequest (basketId) {
  return (post(`/baskets/${basketId}/withdraw`))
}

export async function removeBasket (basketId) {
  return remove(`/baskets/${basketId}`)
}

export async function listBasketCoordinates () {
  return (await get('/baskets?type=coordinates')).baskets
}

export async function getBasketsNearby (lat, lon, distance = 30) {
  if (lat && lon) {
    return (await get(`/baskets/nearby?lat=${lat}&lon=${lon}&distance=${distance}`)).baskets
  }
  throw new Error('Missing lat or lon')
}

export async function addBasket (basketData) {
  return post('/baskets', basketData)
}

export async function editBasket (basketId, basketData) {
  return put(`/baskets/${basketId}`, basketData)
}
