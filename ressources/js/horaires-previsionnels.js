const ca = document.getElementById("c-a")
// const cc = document.getElementById("c-c")
// const cv = document.getElementById("c-v")
if (navigator.platform.includes("Mac")) {
  ca.innerHTML = "⌘-A"
  // cc.innerHTML = "⌘-C"
  // cv.innerHTML = "⌘-V"
}
if (navigator.platform.includes("Linux")) {
  ca.innerHTML = "^A"
  // cc.innerHTML = "^C"
  // cv.innerHTML = '^V'
}

const text = document.getElementById("prevoirText")
const err = document.getElementById("prevoirInfo")
const estats = document.getElementById("prevoirStats")

// exemples :
// BL22	Cours	Mercredi	16:30	18:30	FA100	hebdomadaire
// BL22	TD    Lundi	    16:30	18:30	FA404	hebdomadaire
// BL22	TP    Jeudi	    8:00	12:00	RJ200	tous les 15 jours
const re =
  /([A-Z0-9]{3,6})\s+(Cours|TD|TP)\s+(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi)\s+(\d+:\d+)\s+(\d+:\d+)\s+([A-Z0-9]+)\s+([\w ]+)/g

// exemples :
// BL22	Cours (C)	Mercredi	16:30	18:30	Semaine A et B
// BL22	TD (D 1)	Lundi	    16:30	18:30	Semaine A et B
// BL22	TP (T 1)	Jeudi	    08:00	12:00	A
const re_test =
  /([A-Z0-9]{3,6})\s+(Cours|TD|TP) (\([A-Z](?: \d+)?\))\s+(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi)\s+(\d+:\d+)\s+(\d+:\d+)\s+([\w ]+)/g

const DAYS = {
  Lundi: 0,
  Mardi: 1,
  Mercredi: 2,
  Jeudi: 3,
  Vendredi: 4,
  Samedi: 5
}
const WIDTH = 120
let prevoirPossibilities = []
let prevoirValidPossibilities = []
let prevoirIndex = 0

text.oninput = () => {
  console.time("on input")
  try {
    err.innerHTML = "calcul en cours..."

    console.time("parseCourses")
    const all_courses = parseCourses(text.value)
    console.timeEnd("parseCourses")

    if (all_courses.length == 0) {
      err.innerHTML = "aucun cours"
      return
    }
    else {
      document.getElementById('prevoirText').scrollIntoView()
    }

    // const poss_count = Object.values(all_courses).map(c => c.length).reduce((x, y) => x * y)

    console.time("possibilities")
    prevoirPossibilities = possibilities(all_courses)
    console.timeEnd("possibilities")

    console.time("filter valid")
    prevoirValidPossibilities = prevoirPossibilities.filter(isValid)
    console.timeEnd("filter valid")

    stats(prevoirValidPossibilities)
    prevoirValidPossibilities.sort((a, b) =>
      (b.free_days - a.free_days) || (a.num - b.num))

    err.innerHTML = `${prevoirValidPossibilities.length} valides / ${prevoirPossibilities.length} possibilités`
    $('#prevoirButton').prop('disabled', false)
  }
  catch (error) {
    console.error(error)
    err.innerHTML = 'Erreur: non valide'
    estats.innerHTML = ''
    $('#prevoirButton').prop('disabled', true)
  }
  console.timeEnd("on input")
}

function parseCourses(text) {
  const courses = {}
  while (m = re.exec(text)) {
    m = m.filter(function (val) {
      return val !== undefined
    })
    const [, uv, type, day, h_start, h_end, room, period] = m
    console.log(m)
    let [hs, ms] = h_start.split(":"); hs = +hs + +ms / 60
    let [he, me] = h_end.split(":"); he = +he + +me / 60
    const pnhebdo = /\d+ jours/.test(period) //|| !/Semaine/.test(period)
    const shift = period == "B"
    const p15 = /15 jours/.test(period)
    const obj = { uv, type, day: DAYS[day], hs, he, room, period, pnhebdo, shift, p15, h_start, h_end }
    if (courses[uv + type] === undefined)
      courses[uv + type] = []
    courses[uv + type].push(obj)
  }

  while (m = re_test.exec(text)) {
    m = m.filter(function (val) {
      return val !== undefined
    })
    let [, uv, type, room, day, h_start, h_end, period] = m
    if (uv == 'TX00' || uv == 'PR00')
      continue
    console.log(m)
    room = room.slice(1, -1)
    // c'est plus du tout une room, de toute façon osef tu vois pas la diff
    let [hs, ms] = h_start.split(":"); hs = +hs + +ms / 60
    let [he, me] = h_end.split(":"); he = +he + +me / 60
    const pnhebdo = /\d+ jours/.test(period) || !/Semaine/.test(period)
    const shift = period == "B"
    const p15 = /15 jours/.test(period)
    const obj = { uv, type, day: DAYS[day], hs, he, room, period, pnhebdo, shift, p15, h_start, h_end }
    if (courses[uv + type] === undefined)
      courses[uv + type] = []
    courses[uv + type].push(obj)
  }

  return courses
}

function possibilities(courses, acc = [[]]) {
  const keys = Object.keys(courses)
  if (keys.length) {
    const first = courses[keys[0]]
    acc = [].concat(...first.map(poss => acc.map(a => a.concat(poss))))
    delete courses[keys[0]]
    return possibilities(courses, acc)
  }
  else
    return acc
}

function isValid(timetable) {
  for (let i = 1; i < timetable.length; ++i) {
    const ti = timetable[i]
    for (let j = 0; j < i; ++j) {
      const tj = timetable[j]
      if (ti.day == tj.day && !(tj.he <= ti.hs || tj.hs >= ti.he)) {
        if (ti.pnhebdo && tj.pnhebdo)
          ti.shift = true
        else
          return false
      }
    }
  }
  return true
}

function stats(timetables) {
  const free_days = [0, 0, 0, 0, 0, 0, 0]
  const N = timetables.length
  const p = x => `${(x / N * 100).toPrecision(3)}% (${x}/${N})`

  timetables.forEach((timetable, i) => {
    const s = new Set(timetable.map(c => c.day))
    timetable.num = i
    timetable.free_days = 6 - s.size
    free_days[6 - s.size]++
  })
  for (let i = free_days.length - 2; i; i--) {
    free_days[i] += free_days[i + 1]
  }

  let html = `Chance d'avoir 1 jour libre (incluant le samedi) : ${p(free_days[1])}`
  for (let i = 2; i <= 6; ++i)
    if (free_days[i])
      html += `<br>Chance d'avoir ${i} jours libres : ${p(free_days[i])}`
  estats.innerHTML = html
}

function renderCourse(c) {
  let period = /\d+ jours/.exec(c.period)
  period = period != null ? " " + period[0] : ""
  return (
    `<div ${c.pnhebdo ? "class=nothebdo" : ""} style="
            left: calc(${(c.day + (c.shift ? 0.5 : 0)) / 6 * 100}% + 2px);
            top: ${(c.hs - 8) * 20}px;
            height: ${(c.he - c.hs) * 20}px;"
          title="${c.type} de ${c.uv} en ${c.room}\n${Object.keys(DAYS)[c.day]} ${c.h_start}-${c.h_end} ${c.period}">
        ${c.uv} ${c.type + (c.pnhebdo ? " " + c.period : "")}<br>
        ${c.room}
    </div>`)
}

function renderTimetable(t) {
  return `<h3>EDT n°${t.num + 1} (${t.free_days}j libre${t.free_days <= 1 ? '' : 's'})</h3>
    <div class=calendar>
        ${t.map(renderCourse).join("")}
    </div>`
}
