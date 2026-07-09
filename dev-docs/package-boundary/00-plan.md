# easy-auth ビュー: パッケージ/アプリ境界の適正化 全体計画

## 背景・目的

easy-authが提供するビューは以下の2点を満たす必要がある。

1. アプリがビューを一切用意しなくても、認証方式がひと通り機能することを保証する
2. アプリに対して、Laravelらしい方法で差し替え・カスタマイズの手段を提供する

現状は1のみを満たし、2の手段が無いことがREADME「既知の制限・将来の検討事項」に明記されている。これを解消するため、以下の4層構造を全ビューに適用する。

## アーキテクチャ: 2層構造 × 4レイヤー

### 2層構造(ビューの物理的な分離)

- **機能コンポーネント**: 「1つのフォーム + それ専用のステータス/エラー表示 + WebAuthn儀式やCSRF・X-Device-Uuidヘッダー等の暗黙のJS配線契約」を1単位とする。パッケージの存在意義そのもの。特定のルート/ページに描画される前提を持たない自己完結設計にする(必要なら`session()`等を自分で参照し、controllerからpropsで渡してもらう依存を作らない)。
- **ページ**: コンポーネントの「デフォルトの組み合わせ方」の1例に過ぎない薄いラッパー。`@extends('layouts.app') @section('content') <x-easy-auth::xxx-form /> @endsection`のみを持つ。アプリが独自レイアウト(例: サインインとサインアップを1画面に並べる)を作りたい場合、ページ側には一切手を出さず、自分のルート・自分のビューに同じコンポーネントを配置するだけで済む。

### 4レイヤー(カスタマイズ手段)

| レイヤー | 内容 | 適用範囲 |
|---|---|---|
| Layer 1 | `vendor:publish`によるページ/コンポーネント単位の丸ごと差し替え(Laravel標準の`resources/views/vendor/{namespace}/`解決) | パッケージ全体で1回登録すれば済む(ディレクトリ単位のマッピングのため個別ファイル構成に依存しない) |
| Layer 2 | コンポーネント内の`@stack`/`@push`による差し込みスロット(既存デフォルトのマークアップを保ったまま項目追加) | コンポーネントごとに個別設計 |
| Layer 3 | 主要な変更操作(作成・更新・削除等)の前後に発火するLaravel Event(`validate()`/`fillable`がハードコードされている問題への対応。アプリはリスナーで独自フィールドを直接代入保存する) | コントローラのアクションごとに個別実装 |
| Layer 4 | 機能単位のBladeコンポーネントへの分割そのもの(2層構造の実体) | ビューごとに個別実装(最も規模が大きい) |

## 決定済みの命名・登録規約(全セッション共通、変更不可)

- **コンポーネント配置・命名**: `resources/views/components/{domain}/{name}.blade.php` → `<x-easy-auth::{domain}.{name} />`
  例: `components/auth/sign-in-form.blade.php` → `<x-easy-auth::auth.sign-in-form />`
- **差し込みスロット命名**: `easy-auth::components.{domain}.{name}.{slot}`
  例: `easy-auth::components.tenants.edit-form.after-fields`
- **イベント命名**: `DoITs\EasyAuth\Events\{Model}{Verb}ing` / `{Model}{Verb}ed`(例: `TenantUpdating`/`TenantUpdated`)。Laravel組み込みの`Illuminate\Auth\Events\Registered`等と衝突しない名前にすること。
  - **セッションAでの補足(承認済みの逸脱)**: 動詞の現在分詞・過去分詞が同形になり紛らわしい場合(例: 「reset」)、または対象がEloquentモデル名と1対1で対応しない意味的な単位の場合(例: 「Account」削除、実体は`User`モデルだが概念としては「アカウント削除」)は、型を厳密に適用せず`PasswordResetting`/`PasswordResetCompleted`、`AccountDeleting`/`AccountDeleted`のように意味が明確な名前を優先してよい。Laravel組み込みイベントとの衝突回避が目的の場合は特に、命名の逸脱理由をコメントか報告書に残すこと。
- **publishタグ**: 既存の`easy-auth-config`に倣い、`easy-auth-views`という単一タグで`resources/views`ツリー全体を`views/vendor/easy-auth`へ公開する(ページ単位・コンポーネント単位で別タグに分けない)。

## 追加の設計判断(全ビュー調査後に確定)

### ループ内の要素(`@forelse`)へのLayer2適用は`@stack`ではなく`@includeIf`を使う

`tenants/members/index`(admin/member一覧)・`tenants/invitations/index`(招待一覧)は`@forelse`でループする構造を持つ。`@stack`/`@push`はページ単位で1回しか差し込めないため、ループの各行に対して個別の追加ボタン等を差し込む用途には向かない。ループ内の拡張ポイントには、Laravel標準の`@includeIf`(対象ビューが存在しなければ何も描画しない)を使う。

例: `tenants/member-row.blade.php`コンポーネント内に`@includeIf('vendor.easy-auth.tenants.member-row-actions', ['tenant' => $tenant, 'member' => $member])`を置く。アプリ側は`resources/views/vendor/easy-auth/tenants/member-row-actions.blade.php`を作成すれば、`$tenant`/`$member`を受け取って行ごとに追加ボタン等を描画できる。命名は「差し込み先コンポーネント名+`-actions`」等、対象セッションの裁量で決めてよいが、`@stack`と混同しないよう本ドキュメントでは区別して呼ぶ。

### コピー用ボタンの共通化(セッションC・D間の唯一の依存)

`tenants/backup-code/show.blade.php`(セッションC担当)と`tenants/invitations/create.blade.php`(セッションD担当)に、招待URLを表示してクリップボードにコピーするボタンが、ほぼ同一のインラインJS付きで重複実装されている(`.js-copy-invitation-url`クラス+ページ内`<script>`)。

これは`components/shared/copy-to-clipboard-button.blade.php`(`<x-easy-auth::shared.copy-to-clipboard-button :url="..." :label="..." :label-copied="..." />`)として1箇所に共通化する。**セッションCがこのコンポーネントを新設する担当とし、セッションDはこれを再利用する。** そのため、セッションA・Bとは異なり、セッションDはこの1点に限りセッションCの完了(または少なくともこのコンポーネントの新設完了)を前提とする。並行作業したい場合、セッションDは暫定的に独自実装しておき、セッションC完了後に統合してもよい(このセッション=コントローラがレビュー時に統合する)。

## 既知の共有インフラ課題(セッション0で解消)

`resources/views/partials/js-strings.blade.php`が`<script id="easy-auth-strings">`という単一ID前提で、`resources/js/index.js`の`readStrings()`も`document.getElementById`(単数)で読んでいる。2つ以上のコンポーネントが1ページに同居すると、2つ目以降の`<script id="easy-auth-strings">`が重複IDになり、`getElementById`は最初の1つしか返さないため後続コンポーネントの文言が読み込まれない。全コンポーネントが依存する共有基盤のため、個別セッションが独自に気づいて直すと実装がブレる。セッション0で先に解消し、他セッションへの前提知識とする。

## セッション分割

機能領域(domain)単位で分割する。同一domain内はLayer2/3/4が相互に参照し合うため同一セッションでまとめて扱い、domain間はほぼ独立しているため別セッションに切り出す。

| セッション | 指示書 | 対象ビュー | 前提 |
|---|---|---|---|
| セッション0(共有インフラ) | `session-0-prerequisites.md` | js-strings仕組みの修正、`publishes()`登録、README「既知の制限」該当箇所の更新 | なし(最初に実施) |
| セッションA(auth) | `session-a-auth.md` | `auth/sign-in`, `auth/password-request`, `auth/password-reset`, `auth/account-deletion`, `auth/account-deleted`, `device/reset` | セッション0完了後 |
| セッションB(profile) | `session-b-profile.md` | `profile/create`(3コンポーネントに分割: already-registered-notice / passkey-registration-form / password-registration-form。同一画面トグルはデフォルトページの構成上の選択であり、フォーム自体は独立させておくことでアプリ側が別ルートに分ける構成も選べるようにする), `profile/edit`, `profile/delete` | セッション0完了後 |
| セッションC(tenants) | `session-c-tenants.md` | `tenants/create`, `tenants/edit`, `tenants/delete`, `tenants/leave`, `tenants/members/index`, `tenants/backup-code/show`。`components/shared/copy-to-clipboard-button`も新設 | セッション0完了後 |
| セッションD(invitations) | `session-d-invitations.md` | `invitations/show`, `tenants/invitations/create`, `tenants/invitations/index` | セッション0完了後、コピー用ボタンのみセッションCにも依存 |

セッションA〜Dは基本的に互いに依存せず並行着手可能。ただし「コピー用ボタンの共通化」の1点のみ、セッションDがセッションCの成果物に依存する(詳細は上記「追加の設計判断」参照)。各セッションは完了後、同ディレクトリに`session-{0,a,b,c,d}-report.md`を作成し、実施内容・判断に迷った点・残課題を記録する(コントローラセッションのレビュー入力になる)。

## 完了の定義(全セッション共通)

- 既存のPestテストスイートが全てパスすること(`./vendor/bin/pest`)
- 各セッションが対象とするコントローラ操作について、Layer3のEvent発火を検証するテストを追加すること
- 新設したコンポーネント・スロットについて、既存機能(パスキー登録・サインイン等)の実際の画面/JS配線が壊れていないことを確認すること(このパッケージにJSテストハーネスは無いため、Blade出力のPestテストに加えて、可能であればブラウザでの手動確認を推奨する)
- README「既知の制限・将来の検討事項」該当箇所を、解消済みの内容に更新すること(最終的な文言確定はコントローラセッションが全セッション完了後にレビューして行う)

## 進行管理

このファイルおよび各セッション向け指示書(`session-0-*.md`, `session-a-*.md`等、同ディレクトリ配下)は、本セッション(コントローラ)が作成・更新する。各セッションの実装はレビューを経てから次のセッションに影響する変更(共有ファイルへの追記等)を確定する。
