export const REVIEWS_KEY = "demo_reviews";
export const REPLIES_KEY = "demo_review_replies";

export const DEFAULT_REVIEWS = [
  {
    id: 1,
    author: "Наталья",
    rating: 5,
    date: "2026-02-12",
    text: "Разнообразное меню, внимательный персонал и комфортная атмосфера. Быстро принесли заказ, всё было свежим.",
  },
  {
    id: 2,
    author: "Алексей",
    rating: 4,
    date: "2026-02-10",
    text: "В целом понравилось, особенно подача блюд. Вечером может быть шумно, но обслуживание на хорошем уровне.",
  },
  {
    id: 3,
    author: "Марина",
    rating: 5,
    date: "2026-02-08",
    text: "Отличное место для встреч. Персонал вежливый, помогли с выбором и быстро оформили заказ.",
  },
  {
    id: 4,
    author: "Игорь",
    rating: 4,
    date: "2026-02-06",
    text: "Кухня достойная, понравились горячие блюда. Есть небольшой минус по ожиданию в часы пик.",
  },
  {
    id: 5,
    author: "Ольга",
    rating: 5,
    date: "2026-02-04",
    text: "Были с друзьями, обслуживание отличное, музыка ненавязчивая, внутри уютно и чисто.",
  },
  {
    id: 6,
    author: "Сергей",
    rating: 4,
    date: "2026-02-02",
    text: "Хорошее соотношение цены и качества. Отдельно отмечу вежливость официантов.",
  },
  {
    id: 7,
    author: "Татьяна",
    rating: 5,
    date: "2026-01-30",
    text: "Очень понравилась веранда и атмосфера. Вернёмся ещё.",
  },
  {
    id: 8,
    author: "Евгений",
    rating: 4,
    date: "2026-01-27",
    text: "Удобное расположение, быстро нашли место. Вкусно и аккуратно.",
  },
  {
    id: 9,
    author: "Анна",
    rating: 5,
    date: "2026-01-24",
    text: "Приятный интерьер и отличное обслуживание. Всё понравилось.",
  },
  {
    id: 10,
    author: "Дмитрий",
    rating: 4,
    date: "2026-01-20",
    text: "Неплохой выбор блюд, комфортно, персонал старается.",
  },
  {
    id: 11,
    author: "Елена",
    rating: 5,
    date: "2026-01-16",
    text: "Отмечали событие, всё прошло отлично, спасибо команде.",
  },
  {
    id: 12,
    author: "Павел",
    rating: 4,
    date: "2026-01-11",
    text: "Достойный сервис и вкусная кухня. В целом впечатления положительные.",
  },
];

export function ensureReviews() {
  const raw = localStorage.getItem(REVIEWS_KEY);
  if (!raw) {
    localStorage.setItem(REVIEWS_KEY, JSON.stringify(DEFAULT_REVIEWS));
    return DEFAULT_REVIEWS;
  }

  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed) && parsed.length) {
      return parsed;
    }
  } catch {
    localStorage.setItem(REVIEWS_KEY, JSON.stringify(DEFAULT_REVIEWS));
    return DEFAULT_REVIEWS;
  }

  localStorage.setItem(REVIEWS_KEY, JSON.stringify(DEFAULT_REVIEWS));
  return DEFAULT_REVIEWS;
}

export function saveReviews(reviews) {
  localStorage.setItem(REVIEWS_KEY, JSON.stringify(reviews));
}

export function loadRepliesMap() {
  try {
    return JSON.parse(localStorage.getItem(REPLIES_KEY) || "{}");
  } catch {
    return {};
  }
}

export function saveRepliesMap(replies) {
  localStorage.setItem(REPLIES_KEY, JSON.stringify(replies));
}
