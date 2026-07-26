<?php

Since you're learning **Low-Level Design (LLD)** from scratch, this is one of the most important principles you'll learn. The DRY principle is a foundation for writing clean, maintainable, and reusable code. The AlgoMaster article uses email validation as an example to show why duplicate business logic is dangerous. ([AlgoMaster][1])

You can also read the original lesson here:

**[AlgoMaster - DRY Principle](https://algomaster.io/learn/lld/dry?utm_source=chatgpt.com)**

---

# What is DRY?

**DRY = Don't Repeat Yourself**

The idea is simple:

> **Every piece of knowledge or business rule should exist in only one place in your application.**

Notice that it doesn't say "don't repeat code." It says **don't repeat knowledge**.

For example:

Suppose your company has this rule:

> "A valid email must contain '@' and '.'"

If you write this rule in 10 different files, then the business rule exists in 10 places.

If tomorrow the company changes the rule:

> "Email must end with .com or .org"

Now you must modify all 10 files.

If you forget one file, your application behaves differently in different modules.

That's exactly the problem DRY tries to solve. ([AlgoMaster][1])

---

# Real Life Example

Imagine you work in a company.

There are three departments.

```
Authentication
Payment
Messaging
```

Every department validates email.

Without DRY:

```
AuthService

validateEmail()

-----------------------

PaymentService

validateEmail()

-----------------------

MessagingService

validateEmail()
```

All three contain exactly the same logic.

```
if email contains '@'
and '.'
```

Everything works.

---

Six months later...

Business changes requirement.

Now email should be

```
@gmail.com
@yahoo.com
```

Now you must modify

```
AuthService

PaymentService

MessagingService
```

Imagine your project has

```
400 files
25 developers
```

Someone forgets to update PaymentService.

Now

```
Signup ✔

Message ✔

Payment ❌
```

Customer gets confused.

This bug exists because we violated DRY.

---

# DRY Solution

Instead of writing validation everywhere...

Create one class.

```
EmailValidator

validate()
```

Now every module calls it.

```
AuthService
       |
       |
PaymentService ----> EmailValidator
       |
MessagingService
```

Business changes?

Only change

```
EmailValidator
```

Done.

Everything automatically works.

---

# PHP Example (Without DRY)

```php
class AuthService
{
    public function register($email)
    {
        if ($email == "" || strpos($email, "@") === false) {
            echo "Invalid Email";
        }
    }
}

class PaymentService
{
    public function process($email)
    {
        if ($email == "" || strpos($email, "@") === false) {
            echo "Invalid Email";
        }
    }
}

class MessageService
{
    public function send($email)
    {
        if ($email == "" || strpos($email, "@") === false) {
            echo "Invalid Email";
        }
    }
}
```

Look carefully.

Exactly same code appears

* Auth
* Payment
* Message

This is duplication.

---

# Problems

Tomorrow business says

Email must contain

```
@

.

.com
```

Now we edit

```
AuthService

PaymentService

MessageService
```

Three places.

If there were 50 services?

You edit 50 files.

---

# PHP Example (Using DRY)

Create one reusable class.

```php
class EmailValidator
{
    public static function validate($email)
    {
        if (
            empty($email) ||
            strpos($email, "@") === false ||
            strpos($email, ".") === false
        ) {
            return false;
        }

        return true;
    }
}
```

Now use it everywhere.

```php
class AuthService
{
    public function register($email)
    {
        if (!EmailValidator::validate($email)) {
            echo "Invalid Email";
            return;
        }

        echo "Registration Successful";
    }
}
```

Payment

```php
class PaymentService
{
    public function process($email)
    {
        if (!EmailValidator::validate($email)) {
            echo "Invalid Email";
            return;
        }

        echo "Payment Successful";
    }
}
```

Messaging

```php
class MessageService
{
    public function send($email)
    {
        if (!EmailValidator::validate($email)) {
            echo "Invalid Email";
            return;
        }

        echo "Message Sent";
    }
}
```

Now validation exists in **one place only**.

---

# Later Business Changes

Old Rule

```
Contains @
Contains .
```

New Rule

```
Contains @
Contains .
Ends with .com
```

Change only this file.

```php
class EmailValidator
{
    public static function validate($email)
    {
        if (
            empty($email) ||
            strpos($email, "@") === false ||
            strpos($email, ".") === false ||
            !str_ends_with($email, ".com")
        ) {
            return false;
        }

        return true;
    }
}
```

No need to touch

```
AuthService

PaymentService

MessageService
```

That's DRY.

---

# Another Real Example

Suppose every service calculates GST.

Bad Design

```
OrderService

GST = amount * 18%

--------------------

InvoiceService

GST = amount * 18%

--------------------

PaymentService

GST = amount * 18%
```

One day GST becomes

```
20%
```

Need to edit 3 files.

Instead

```
TaxCalculator

calculateGST()
```

Everyone calls

```
TaxCalculator
```

Only one change.

---

# When Should You Apply DRY?

Apply DRY when:

* Same business rule appears in multiple places.
* Same calculation is copied.
* Same validation is copied.
* Same database query logic is repeated.
* Same email/SMS formatting is repeated.

---

# When NOT to Apply DRY

Don't force DRY too early.

Example:

```php
calculateSalary()

calculateBonus()
```

Both currently multiply by 10%.

Although they look similar today, they may change independently tomorrow. Merging them prematurely can make the code harder to maintain. A common guideline is the **Rule of Three**: when the same logic appears three or more times, it's usually a good candidate for extraction into a shared function or class. ([AlgoMaster][1])

---

# Interview Definition

If an interviewer asks:

> **What is the DRY Principle?**

A concise answer is:

> **DRY (Don't Repeat Yourself) means every piece of business logic or knowledge should exist in one place only. Instead of duplicating logic across multiple classes, extract it into a reusable component so changes need to be made only once. This improves maintainability, reduces bugs, and keeps the codebase consistent.**

---

## My recommendation for your LLD journey

Based on your previous questions, you're preparing for senior backend and Tech Lead interviews. Learn the design principles in this order:

1. DRY
2. KISS
3. YAGNI
4. SOLID (all five principles)
5. Composition over Inheritance
6. Design Patterns (Factory, Strategy, Observer, Decorator, Adapter, Proxy, etc.)

This sequence matches a natural progression from writing clean code to designing flexible object-oriented systems, and aligns well with common LLD interview expectations. ([AlgoMaster][2])

[1]: https://algomaster.io/learn/lld/dry?utm_source=chatgpt.com "DRY Principle | LLD"
[2]: https://algomaster.io/learn/lld/course-roadmap?utm_source=chatgpt.com "Course Roadmap | LLD"
