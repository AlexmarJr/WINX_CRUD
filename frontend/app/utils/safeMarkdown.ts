function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, character => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[character] ?? character)
}

function renderInline(value: string): string {
  const pattern = /`([^`\n]+)`|\[([^\]\n]+)\]\(([^)\s]+)\)|\*\*([^*\n]+)\*\*|\*([^*\n]+)\*/g
  let html = ''
  let offset = 0

  for (const match of value.matchAll(pattern)) {
    const index = match.index ?? 0
    html += escapeHtml(value.slice(offset, index))

    if (match[1] !== undefined) {
      html += `<code>${escapeHtml(match[1])}</code>`
    } else if (match[2] !== undefined && match[3] !== undefined) {
      try {
        const url = new URL(match[3])
        if (url.protocol === 'https:' || url.protocol === 'http:') {
          html += `<a href="${escapeHtml(url.href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(match[2])}</a>`
        } else {
          html += escapeHtml(match[0])
        }
      } catch {
        html += escapeHtml(match[0])
      }
    } else if (match[4] !== undefined) {
      html += `<strong>${escapeHtml(match[4])}</strong>`
    } else if (match[5] !== undefined) {
      html += `<em>${escapeHtml(match[5])}</em>`
    }

    offset = index + match[0].length
  }

  return html + escapeHtml(value.slice(offset))
}

function tableCells(line: string): string[] {
  return line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map(cell => cell.trim())
}

function isTableSeparator(line: string): boolean {
  return /^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?$/.test(line.trim())
}

function isListItem(line: string): boolean {
  return /^\s*(?:[-*]|\d+\.)\s+/.test(line)
}

export function renderSafeMarkdown(markdown: string): string {
  const lines = markdown.replace(/\r\n?/g, '\n').split('\n')
  const blocks: string[] = []

  for (let index = 0; index < lines.length;) {
    const line = lines[index] ?? ''

    if (!line.trim()) {
      index++
      continue
    }

    if (/^\s*```/.test(line)) {
      const code: string[] = []
      index++
      while (index < lines.length && !/^\s*```/.test(lines[index] ?? '')) {
        code.push(lines[index] ?? '')
        index++
      }
      if (index < lines.length) index++
      blocks.push(`<pre><code>${escapeHtml(code.join('\n'))}</code></pre>`)
      continue
    }

    if (line.includes('|') && index + 1 < lines.length && isTableSeparator(lines[index + 1] ?? '')) {
      const header = tableCells(line).map(cell => `<th>${renderInline(cell)}</th>`).join('')
      index += 2
      const rows: string[] = []
      while (index < lines.length && (lines[index] ?? '').includes('|') && (lines[index] ?? '').trim()) {
        rows.push(`<tr>${tableCells(lines[index] ?? '').map(cell => `<td>${renderInline(cell)}</td>`).join('')}</tr>`)
        index++
      }
      blocks.push(`<div class="ai-chat-table-wrap"><table><thead><tr>${header}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`)
      continue
    }

    const heading = line.match(/^(#{1,3})\s+(.+)$/)
    if (heading) {
      blocks.push(`<h${heading[1]?.length}>${renderInline(heading[2] ?? '')}</h${heading[1]?.length}>`)
      index++
      continue
    }

    if (isListItem(line)) {
      const ordered = /^\s*\d+\./.test(line)
      const items: string[] = []
      while (index < lines.length && isListItem(lines[index] ?? '') && /^\s*\d+\./.test(lines[index] ?? '') === ordered) {
        items.push(`<li>${renderInline((lines[index] ?? '').replace(/^\s*(?:[-*]|\d+\.)\s+/, ''))}</li>`)
        index++
      }
      const tag = ordered ? 'ol' : 'ul'
      blocks.push(`<${tag}>${items.join('')}</${tag}>`)
      continue
    }

    const paragraph: string[] = [line]
    index++
    while (index < lines.length && (lines[index] ?? '').trim()
      && !/^\s*```/.test(lines[index] ?? '')
      && !/^(#{1,3})\s+/.test(lines[index] ?? '')
      && !isListItem(lines[index] ?? '')
      && !(index + 1 < lines.length && (lines[index] ?? '').includes('|') && isTableSeparator(lines[index + 1] ?? ''))) {
      paragraph.push(lines[index] ?? '')
      index++
    }
    blocks.push(`<p>${paragraph.map(renderInline).join('<br>')}</p>`)
  }

  return blocks.join('')
}
