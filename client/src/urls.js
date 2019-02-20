// these are used for generating link-paths inside vue
// e.g. $url('profile', 15)

export default {
  profile: (id) => `/profile/${id}`,
  store: (id) => `/?page=betrieb&id=${id}`,
  forum: (regionId, regionSubId = 0) => `/?page=bezirk&bid=${regionId}&sub=${regionSubId === 1 ? 'botforum' : 'forum'}`,
  fairteiler: (regionId) => `/?page=bezirk&bid=${regionId}&sub=fairteiler`,
  events: (regionId) => `/?page=bezirk&bid=${regionId}&sub=events`,
  stores: (regionId) => `/?page=betrieb&bid=${regionId}`,
  storeAdd: (regionId = null) => regionId ? `/?page=betrieb&a=new&bid=${regionId}` : `/?page=betrieb&a=new`,
  workingGroups: (regionId = null) => regionId ? `/?page=groups&p=${regionId}` : `/?page=groups`,
  workingGroupEdit: (groupId) => `/?page=groups&sub=edit&id=${groupId}`,
  wall: (regionId) => `/?page=bezirk&bid=${regionId}&sub=wall`,
  foodsaverList: (regionId) => `/?page=foodsaver&bid=${regionId}`,
  passports: (regionId) => `/?page=passgen&bid=${regionId}`,
  conversations: () => `/?page=msg`,
  dashboard: () => `/?page=dashboard`,
  map: () => `/karte`,
  home: () => `/`,
  mailbox: () => `/?page=mailbox`,
  settings: () => `/?page=settings`,
  logout: () => `/?page=logout`,
  joininfo: () => `/?page=content&sub=joininfo`,
  basket: (basketId) => `/essenskoerbe/${basketId}`,
  baskets: () => `/essenskoerbe`,
  upgradeToFs: () => `/?page=settings&sub=upgrade/up_fs`,

  mission: () => `/ueber-uns`,
  claims: () => `/?page=content&sub=forderungen`,
  fasten: () => `/?page=content&sub=fasten`,
  leeretonne: () => `/?page=content&sub=leeretonne`,
  academy: () => `/?page=content&sub=academy`,
  workshops: () => `/?page=content&sub=workshops`,
  festival: () => `/?page=content&sub=festival`,
  international: () => `/?page=content&sub=international`,
  transparency: () => `/?page=content&sub=transparency`,
  contact: () => `/?page=content&sub=contact`,
  dataprivacy: () => `/?page=legal`,
  partner: () => `/partner`,
  statistics: () => `/statistik`,
  infosCompany: () => `/fuer-unternehmen`,
  infos: () => `/?page=content&sub=infohub`,
  blog: () => `/news`,
  faq: () => `/faq`,
  guide: () => `/ratgeber`,
  wiki: () => `https://wiki.foodsharing.de/`,
  grundsaetze: () => `https://wiki.foodsharing.de/Grundsätze`,
  communitiesGermany: () => `/?page=content&sub=communitiesGermany`,
  communitiesAustria: () => `/?page=content&sub=communitiesAustria`,
  communitiesSwitzerland: () => `/?page=content&sub=communitiesSwitzerland`,
  team: () => `/team`,
  press: () => `/?page=content&sub=presse`,
  imprint: () => `/impressum`,
  donate: () => `/unterstuetzung`,
  changelog: () => `/?page=content&sub=changelog`
}
