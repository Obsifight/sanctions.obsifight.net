$('.ui.dropdown').dropdown()


function formatSeconds (s, brut) {
  var fm = [
    Math.floor(s / 60 / 60 / 24), // DAYS
    Math.floor(s / 60 / 60) % 24, // HOURS
    Math.floor(s / 60) % 60, // MINUTES
    s % 60 // SECONDS
  ]
  var result = $.map(fm, function (v, i) {
    return v
  })

  if (brut)
    return result

  // formatting to string
  var durationFormatted = ''

  if (result[0] > 0)
    durationFormatted += result[0] + ' jours '
  if (result[1] > 0)
    durationFormatted += result[1] + ' heures '
  if (result[2] > 0)
    durationFormatted += result[2] + ' minutes '
  if (result[3] > 0)
    durationFormatted += result[3] + ' secondes '

  return durationFormatted.slice(0, -1)
}
