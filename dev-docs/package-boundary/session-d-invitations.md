# セッションD: invitationsドメインのビュー境界適正化

このファイルは、easy-authリポジトリのルートで新しく開始するClaude Codeセッションにそのまま渡すための指示書です。実行前に`dev-docs/package-boundary/00-plan.md`(全体アーキテクチャ・命名規約、特に「ループ内の要素へのLayer2適用」「コピー用ボタンの共通化」の節)を読み、**セッション0が完了済みであること**を確認してから着手してください。

**このセッションは`components/shared/copy-to-clipboard-button.blade.php`(セッションC担当)に依存します。** 着手前に、セッションCがこのコンポーネントを作成済みか(`dev-docs/package-boundary/session-c-report.md`が存在し、当該コンポーネントについて記載があるか)を確認してください。

- 未完了の場合: `tenants/invitations/create.blade.php`のコピー機能は暫定的に現状のインラインスクリプトのまま残し、他の作業(コンポーネント分割・スロット・イベント)を先に進めてよい。コピー機能の統合はコントローラセッションのレビュー時に行う。
- 完了済みの場合: `session-c-report.md`に記載された最終的なprops仕様(`:url`, `:label`, `:label-copied`等)に合わせて`<x-easy-auth::shared.copy-to-clipboard-button />`を使うこと。

## 対象ビュー

- `resources/views/invitations/show.blade.php`(招待redeem画面、ルート上は`tenants`配下ではない)
- `resources/views/tenants/invitations/create.blade.php`
- `resources/views/tenants/invitations/index.blade.php`

対応するコントローラ: `InvitationController`(create/store/index/destroy), `InvitationRedemptionController`(redeem)。

コンポーネントの配置場所は`components/tenants/`ではなく**`components/invitations/`ドメインに統一する**(現状のビューファイルパスは`tenants/invitations/`配下と`invitations/`直下に分かれているが、機能的にはすべて「招待」ドメインのため、コンポーネント側では`invitations`に揃える)。

## コンポーネント分割(Layer4)

- `components/invitations/redeem-panel.blade.php` → `<x-easy-auth::invitations.redeem-panel />`(`invitations/show.blade.php`の`$status`/`$alreadyMember`/`$isPromotion`/`$invitation`/`$token`による4分岐〈invalid/already_admin/already_member/通常〉をそのまま1コンポーネントに集約する。分岐ごとの文言・ボタンが密結合しているため、無理に分割しない)
- `components/invitations/create-form.blade.php` → `<x-easy-auth::invitations.create-form />`(`$tenant`, `$invitationUrl`, `$invitationQrCode`, `$isAdmin`, `$defaultExpiresAt`を受け取る)
- `components/invitations/invitation-row.blade.php` → `<x-easy-auth::invitations.invitation-row />`(`$tenant`, `$invitation`を受け取る。`@forelse`内の1件分のカードを切り出す)
- `components/invitations/list.blade.php` → `<x-easy-auth::invitations.list />`(`$tenant`, `$invitations`を受け取り、`invitation-row`をループ呼び出しする外殻)

各ページは薄いラッパーにする。

## Layer2: 差し込みスロット

- `easy-auth::components.invitations.create-form.after-fields`(既存の`label`/`expires_at`/`max_uses`/管理者用チェックボックスの後、submitボタンの前)
- `easy-auth::components.invitations.redeem-panel.after-message`(4分岐それぞれの文言の後に置くことを想定するが、分岐が複数あるため設置箇所は担当セッションの裁量で判断してよい。無理に全分岐に均等設置しなくてよい)

## Layer2(ループ用): `@includeIf`

`invitation-row.blade.php`内、既存の`@can('delete', $invitation)`ブロックの後に以下を追加する。

```blade
@includeIf('vendor.easy-auth.invitations.invitation-row-actions', ['tenant' => $tenant, 'invitation' => $invitation])
```

## Layer3: イベント

- `InvitationController::store`前後: `InvitationCreating`/`InvitationCreated`
- `InvitationController::destroy`(revoke)前後: `InvitationRevoking`/`InvitationRevoked`
- `InvitationRedemptionController::redeem`前後: `InvitationRedeeming`/`InvitationRedeemed`(既存の`RedeemPendingInvitation`アクションがredeemした`Invitation`を返す設計になっているはずなので、イベントにはそのInvitationと、redeemした側のUserを両方持たせること)

## やらないこと

- `auth`・`profile`・`tenants`ドメインのビュー(他セッション担当)
- `copy-to-clipboard-button`コンポーネント自体の新規実装(セッションC担当、上記の通り未完了なら暫定対応で進める)

## 完了条件

- `./vendor/bin/pest`が全件パスすること(特に招待作成・revoke・redeem関連のテスト、バックアップコードredeemを含む)
- `redeem-panel`の4分岐(invalid/already_admin/already_member(昇格)/already_member(同ロール)/通常)がそれぞれ元の文言通りに描画されることを確認するテストの通過を確認すること
- 上記全イベントがFeatureテストで発火を検証されていること
- `copy-to-clipboard-button`が未統合のまま完了する場合、`session-d-report.md`にその旨を明記し、コントローラセッションでの統合待ちであることが分かるようにすること
- このファイルと同じディレクトリに`session-d-report.md`として、実施内容・判断に迷った点・残課題を記録すること
