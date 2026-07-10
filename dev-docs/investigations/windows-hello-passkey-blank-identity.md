# Windows Helloパスキーサインイン不具合 調査結論(2026-07-06)

## 症状

パスキー登録済みの端末でサインアウト後、「サインイン」を押してもWindows Helloの認証ダイアログが一切表示されず、無言で失敗する(パスワードサインイン画面へのフォールバック、または何も起きない)。

## 確定した根本原因

`src/Http/Controllers/ProfileController.php`の`passkeyOptions()`(新規登録用のWebAuthn登録オプション生成)が、まだ実ユーザーが存在しない段階で以下のように**`name`を空文字にしたダミーのUserインスタンス**を使っている。

```php
$options = $generate(new $userModel(['name' => '']));
```

`laravel/passkeys`の`PasskeyAuthenticatable`トレイト(`vendor/laravel/passkeys/src/PasskeyAuthenticatable.php`)の実装:

```php
public function getPasskeyDisplayName(): string
{
    return $this->getAttribute('name') ?? $this->getAttribute('email') ?? (string) $this->getAuthIdentifier();
}
public function getPasskeyUsername(): string
{
    return $this->getAttribute('email') ?? (string) $this->getAuthIdentifier();
}
```

PHPの`??`は`null`にしかフォールバックしない。`name`は`null`ではなく空文字`''`を明示的にセットしているため`displayName`はそのまま`''`。`email`は未設定(`null`)、`getAuthIdentifier()`も未保存モデルのため`null`→`(string)null`で`''`となり、`username`も`''`になる。

**結果、easy-authで新規登録されるパスキーは、WebAuthnの`user.name`/`user.displayName`が必ず両方とも空文字で作成される。** これは偶発的な不具合ではなく、登録するたびに確実に発生する。

この「名前無し」の resident credential が存在すると、Windows Hello側で**discoverable(`allowCredentials`を空にした、ユーザー名を指定しないusernameless)なサインイン要求の列挙処理自体が機能不全になり**、`navigator.credentials.get()`が`NotAllowedError`で即座に失敗し、認証ダイアログすら表示されなくなることを実機検証で確認済み。easy-auth(`@laravel/passkeys`のデフォルト)のサインイン実装は常にこのdiscoverable/空`allowCredentials`方式を使うため、登録した本人が確実にサインインできなくなる。

### 検証で確認した傍証

- ライブラリ・アプリのJSコードを一切経由しない生の`navigator.credentials.get()`でも同じ`NotAllowedError`が再現(アプリのJS実装自体は無罪)。
- 該当rpIdの resident credential に「名前無し」のものが混在する状態では、空の`allowCredentials`によるGETが確実に失敗する。
- 「名前無し」エントリを削除し、名前付きの資格情報のみが残る状態にすると、同じ空の`allowCredentials`によるGETが(選択ダイアログすら出さずに)成功する。
- `allowCredentials`に特定の資格情報IDを明示するnon-discoverableなGETは、名前無しエントリが混在していても比較的成功しやすい(全件列挙を経由しないため)。
- GitHub・`login.microsoft.com`など他サービスのパスキーでは一度も同じ症状が起きていない(いずれも自前の登録フローで名前付きの資格情報を作成しているため)。

## 修正方針

登録フォームの「お名前」欄は、パスキー登録(`Passkeys.register()`呼び出し)前に既に入力済みでJS側では値を取得できているが、現状はWebAuthn登録オプション取得(`/profile/passkey-options`へのGET)にその値を渡す経路が無く、本名の反映は登録完了後の`PATCH /profile`まで遅れてしまっている。

`laravel/passkeys`本体には手を加えず、easy-auth側の2箇所のみ変更する。

1. **`resources/js/index.js`**: `profileCreateForm`のsubmitハンドラ内、`Passkeys.register()`に渡す`routes.options`を、入力済みの`name`をクエリ文字列に付加した形(例: `` `/profile/passkey-options?name=${encodeURIComponent(name)}` ``)に組み立て直す。
2. **`src/Http/Controllers/ProfileController.php`**: `passkeyOptions()`で、そのクエリパラメータを読み取り、空文字の代わりにダミーUserの`name`として使う。

これにより、登録時点でWindows Hello側に実際のユーザー名が刻まれ、「名前無し」resident credentialが生成されなくなる見込み。

## 実装状況

- 上記2箇所は実装済み(2026-07-06)。
- **実機確認済み(2026-07-10)**: Packagist公開前チェックリストの一環でWindows 11 + 1Password環境をあらためて検証した際、修正後もWindows Helloが認証器として選択できない事象に遭遇したが、原因はこの本来のバグそのものではなく、**修正前(2026-07-06以前)に登録された「名前無し」resident credentialが端末に残存していたこと**だった。この名前無しエントリを削除した時点でHelloが選択可能に戻ったことを確認しており、上記の根本原因診断(34-40行目の傍証と完全に一致)が正しかったことも合わせて裏付けられた。
- **既存の(名前無しで登録済みの)ユーザーへの救済策**: 上記の実機確認により判明。名前無しのresident credentialを端末側のパスキー管理(Windowsの場合「設定」→「アカウント」→「パスキー」等)から削除すれば、以後は新規登録分(名前付き)のみが残りサインインが復旧する。アプリ側でこれを自動検出・案内する仕組みは無く、手動対応が必要。
- README等への「サインインできない場合はブラウザ/OSのパスキー設定を確認」的なトラブルシューティング記載は、未検討のまま残っている(2026-07-06修正以降に登録したユーザーには発生しないため、影響は限定的)。
