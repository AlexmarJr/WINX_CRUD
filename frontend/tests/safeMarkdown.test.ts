import assert from 'node:assert/strict'
import test from 'node:test'
import { renderSafeMarkdown } from '../app/utils/safeMarkdown.ts'

test('renders lists, tables and code blocks', () => {
  const html = renderSafeMarkdown('# Resumo\n\n- Mouse\n- Teclado\n\n| Produto | Estoque |\n| --- | --- |\n| Mouse | 5 |\n\n```js\nconst total = 5\n```')
  assert.match(html, /<h1>Resumo<\/h1>/)
  assert.match(html, /<ul><li>Mouse<\/li><li>Teclado<\/li><\/ul>/)
  assert.match(html, /<table>/)
  assert.match(html, /<pre><code>const total = 5<\/code><\/pre>/)
})

test('escapes HTML and rejects unsafe links', () => {
  const html = renderSafeMarkdown('<img src=x onerror=alert(1)>\n\n[clique](javascript:alert(1))\n\n[site](https://example.com)')
  assert.doesNotMatch(html, /<img|href="javascript:/)
  assert.match(html, /&lt;img src=x onerror=alert\(1\)&gt;/)
  assert.match(html, /href="https:\/\/example.com\/"/)
})
