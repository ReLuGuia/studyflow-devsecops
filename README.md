# StudyFlow: DevSecOps Pipeline

Aplicação PHP de gestão de estudos (StudyFlow) usada como base prática para implementar um pipeline de segurança completo, cobrindo as quatro frentes centrais de AppSec/DevSecOps: **SAST**, **secret scanning**, **SCA** e **DAST**.

O objetivo deste repositório não é a aplicação em si, mas demonstrar a aplicação prática de segurança em um ciclo de desenvolvimento real — incluindo a identificação e correção de vulnerabilidades reais encontradas durante o processo.

## Pipeline de Segurança (CI/CD)

Workflow configurado em `.github/workflows/ci.yml`, executado automaticamente a cada `push` e `pull request` na branch `main`.

| Ferramenta | Categoria | Função |
|---|---|---|
| [Gitleaks](https://github.com/gitleaks/gitleaks) | Secret Scanning | Detecta credenciais, tokens e chaves expostas acidentalmente no código |
| [Semgrep](https://semgrep.dev/) | SAST | Análise estática de código, com regras específicas para PHP |
| [Dependabot](https://docs.github.com/en/code-security/dependabot) | SCA | Monitora e atualiza dependências vulneráveis (GitHub Actions) |
| [OWASP ZAP](https://www.zaproxy.org/) | DAST | Scan dinâmico contra a aplicação em execução |

## Vulnerabilidade Real Encontrada e Corrigida

Na primeira execução do pipeline, o **Semgrep identificou 8 ocorrências** da regra `php.lang.security.injection.echoed-request.echoed-request`, referente a uma vulnerabilidade de **Cross-Site Scripting (XSS) refletido**.

**Causa:** parâmetros vindos de `$_GET` (ex: `disciplina_id`, `assunto_id`, `id`) eram impressos diretamente no HTML sem sanitização:

```php
// Vulnerável
<a href="create.php?disciplina_id=<?php echo $disciplina_id; ?>">
```

**Correção aplicada:** sanitização de toda saída de dados de entrada do usuário com `htmlentities()`, neutralizando a injeção de HTML/JS malicioso:

```php
// Corrigido
<a href="create.php?disciplina_id=<?php echo htmlentities($disciplina_id); ?>">
```

Arquivos corrigidos:
- `pages/assuntos/index.php`
- `pages/disciplinas/assuntos/index.php`
- `pages/sessoes/index.php`
- `pages/sessoes/iniciar.php`

Após a correção, o pipeline passou a executar com sucesso (0 findings bloqueantes).

## Scan Dinâmico (DAST) — OWASP ZAP

Relatório completo disponível em [`docs/zap-report.html`](docs/zap-report.html).

Resumo dos 18 alertas encontrados, por severidade:

| Risco | Quantidade | Status |
|---|---|---|
| Médio | 6 | Parcialmente corrigido |
| Baixo | 7 | Parcialmente corrigido |
| Informativo | 3 | Documentado |

### Correções aplicadas (via `.htaccess`)
- ✅ `Content-Security-Policy` configurado
- ✅ `X-Frame-Options: DENY` (proteção contra clickjacking)
- ✅ `X-Content-Type-Options: nosniff` (proteção contra MIME-sniffing)

### Findings documentados (não corrigidos nesta versão)
- **Ausência de token Anti-CSRF** no formulário de login. Requer implementação de geração/validação de token na sessão (próximo passo planejado)
- **Vazamento de versão do servidor** no header `Server` (Apache/OpenSSL/PHP). Mitigação via `ServerTokens` no `httpd.conf`, pendente de validação no ambiente local
- **Cookies sem flags `HttpOnly`/`SameSite`**. Requer ajuste de configuração de sessão PHP

Manter findings documentados sem correção imediata é uma prática comum em avaliações de segurança reais — a lista acima representa o backlog de melhorias identificado pelo próprio processo de DevSecOps.

## Stack

PHP · MySQL · Apache (XAMPP, ambiente local) · GitHub Actions

## Como rodar localmente

1. Clone o repositório
2. Sirva a pasta via Apache (ex: XAMPP, em `htdocs/studyflow`)
3. Configure o banco de dados MySQL conforme `includes/config.php`
4. Acesse `http://localhost/studyflow`

## Autor

Lucas Fernandes Dias — [LinkedIn](https://www.linkedin.com/in/lucas-fernandes-dias-)